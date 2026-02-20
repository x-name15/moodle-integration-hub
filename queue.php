<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Queue Monitor for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/task/queue_manager.php');

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:view', $context);

$canmanage = has_capability('local/integrationhub:manage', $context);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$taskid = optional_param('taskid', 0, PARAM_INT);
$dlqid = optional_param('dlqid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrationhub/queue.php'));
$PAGE->set_title(get_string('queue', 'local_integrationhub'));
$PAGE->set_heading(get_string('queue', 'local_integrationhub'));
$PAGE->set_pagelayout('admin');

// Process actions.
if ($canmanage && !empty($action) && confirm_sesskey()) {
    if ($action === 'retry' && $taskid > 0) {
        if (\local_integrationhub\task\queue_manager::retry_task($taskid)) {
            \core\notification::success(get_string('task_retried', 'local_integrationhub'));
        } else {
            \core\notification::error(get_string('task_retry_failed', 'local_integrationhub'));
        }
        redirect($PAGE->url);
    }

    if ($action === 'deletetask' && $taskid > 0) {
        if (\local_integrationhub\task\queue_manager::delete_task($taskid)) {
            \core\notification::success(get_string('task_deleted', 'local_integrationhub'));
        } else {
            \core\notification::error(get_string('task_delete_failed', 'local_integrationhub'));
        }
        redirect($PAGE->url);
    }

    if ($action === 'purgeorphans') {
        $count = \local_integrationhub\task\queue_manager::purge_orphan_tasks();
        \core\notification::success(get_string('orphans_purged', 'local_integrationhub', $count));
        redirect($PAGE->url);
    }

    if ($action === 'replay_dlq' && $dlqid > 0) {
        $dlqitem = $DB->get_record('local_integrationhub_dlq', ['id' => $dlqid]);
        if ($dlqitem) {
            // Find the rule.
            $rule = $DB->get_record('local_integrationhub_rules', ['eventname' => $dlqitem->eventname, 'serviceid' => $dlqitem->serviceid]);
            if ($rule) {
                $task = new \local_integrationhub\task\dispatch_event_task();
                $task->set_custom_data([
                    'ruleid' => $rule->id,
                    'eventdata' => json_decode($dlqitem->payload, true),
                ]);
                \core\task\manager::queue_adhoc_task($task);
                $DB->delete_records('local_integrationhub_dlq', ['id' => $dlqid]);
                \core\notification::success(get_string('dlq_replayed', 'local_integrationhub'));
            } else {
                \core\notification::error('Rule not found for this event.');
            }
        }
        redirect($PAGE->url);
    }

    if ($action === 'delete_dlq' && $dlqid > 0) {
        $DB->delete_records('local_integrationhub_dlq', ['id' => $dlqid]);
        \core\notification::success(get_string('dlq_deleted', 'local_integrationhub'));
        redirect($PAGE->url);
    }
}

// Get tasks.
$tasks = \local_integrationhub\task\queue_manager::get_pending_tasks();
$dlqitems = $DB->get_records('local_integrationhub_dlq', null, 'timecreated DESC');

// ---- OUTPUT ----
echo $OUTPUT->header();

// Tabs navigation.
echo html_writer::start_div('mb-4');
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs']);
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/index.php'), get_string('services', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/rules.php'), get_string('rules', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/queue.php'), get_string('queue', 'local_integrationhub'), ['class' => 'nav-link active']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/events.php'), get_string('sent_events', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::end_tag('ul');
echo html_writer::end_div();

// CSS Fix for Moodle Themes (Force dark text on hover).
echo html_writer::tag('style', "
    .table-hover tbody tr:hover { color: #212529 !important; }
    .table-striped tbody tr:nth-of-type(odd) { color: #212529 !important; }
");

echo html_writer::tag('h4', get_string('queue', 'local_integrationhub'), ['class' => 'mb-3']);
echo html_writer::tag('p', get_string('queue_desc', 'local_integrationhub'), ['class' => 'text-muted']);

// Purge orphans button (only if there are tasks with deleted rules).
$hasorphans = false;
foreach ($tasks as $t) {
    if (strpos($t->eventname, 'Unknown') !== false || strpos($t->servicename, 'Unknown') !== false) {
        $hasorphans = true;
        break;
    }
}
if ($canmanage && $hasorphans) {
    echo '<div class="mb-3">';
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url->out_omit_querystring(),
        'style' => 'display: inline;',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'purgeorphans']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag('button', '<i class="fa fa-broom"></i> ' . get_string('purge_orphans', 'local_integrationhub'), [
        'type' => 'submit',
        'class' => 'btn btn-warning btn-sm',
        'onclick' => "return confirm('" . addslashes_js(get_string('purge_orphans_confirm', 'local_integrationhub')) . "');",
    ]);
    echo html_writer::end_tag('form');
    echo '</div>';
}

if (empty($tasks)) {
    echo html_writer::div(get_string('no_pending_tasks', 'local_integrationhub'), 'alert alert-success');
} else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive mb-5']);
    // Force text-dark to avoid theme white-text issues
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'style' => 'color: #212529 !important;']);
    echo '<thead class="table-dark"><tr>';
    echo '<th>' . get_string('col_event', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_service', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_failures', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_next_run', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_created', 'local_integrationhub') . '</th>';
    if ($canmanage) {
        echo '<th>' . get_string('col_actions', 'local_integrationhub') . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($tasks as $task) {
        echo '<tr>';

        // Event Name.
        echo html_writer::tag('td', s($task->eventname));

        // Service Name.
        echo html_writer::tag('td', s($task->servicename));

        // Failures badge.
        $failclass = $task->faildelay > 0 ? 'badge bg-danger' : 'badge bg-secondary';
        $status = $task->faildelay > 0 ? get_string('failed', 'local_integrationhub') : get_string('pending', 'local_integrationhub');
        echo html_writer::tag('td', html_writer::tag('span', $status, ['class' => $failclass]));

        // Next Run.
        $nextrun = $task->nextruntime;
        $timestr = userdate($nextrun);

        if ($task->nextruntime < time()) {
            $timestr .= ' <span class="badge bg-warning text-dark">Overdue</span>';
        }
        echo html_writer::tag('td', $timestr);

        // Created.
        echo html_writer::tag('td', userdate($task->timecreated));

        // Actions.
        if ($canmanage) {
            echo '<td>';

            // View Payload Button (Pending/Failed Tasks).
            // We extract eventdata from customdata.
            $cdata = json_decode($task->customdata);
            $payloaddata = $cdata->eventdata ?? $cdata; // Fallback to full customdata if eventdata is missing.
            $payloadview = json_encode($payloaddata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($payloadview === false) {
                $payloadview = json_last_error_msg();
            }

            echo html_writer::tag('button', '<i class="fa fa-code"></i>', [
                'class' => 'btn btn-sm btn-info me-1 ih-view-payload',
                'type' => 'button',
                'title' => get_string('view_payload', 'local_integrationhub'),
                'data-payload' => $payloadview, // s() escapes HTML entities safely
                'data-title' => get_string('payload_source', 'local_integrationhub') . ': ' . s($task->eventname),
            ]);

            if ($task->faildelay > 0 || $task->nextruntime < time()) {
                // Retry button.
                $retryurl = new moodle_url($PAGE->url, [
                    'action' => 'retry',
                    'taskid' => $task->id,
                    'sesskey' => sesskey(),
                ]);
                echo html_writer::link($retryurl, '<i class="fa fa-refresh"></i>', [
                    'class' => 'btn btn-sm btn-primary me-1',
                    'title' => get_string('retry', 'local_integrationhub'),
                ]);
            }
            // Delete button (always visible).
            $deleteurl = new moodle_url($PAGE->url, [
                'action' => 'deletetask',
                'taskid' => $task->id,
                'sesskey' => sesskey(),
            ]);
            echo html_writer::link($deleteurl, '<i class="fa fa-trash"></i>', [
                'class' => 'btn btn-sm btn-outline-danger',
                'title' => get_string('delete'),
                'onclick' => "return confirm('" . addslashes_js(get_string('task_delete_confirm', 'local_integrationhub')) . "');",
            ]);
            echo '</td>';
        }

        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

// --- DLQ Section ---
echo html_writer::tag('hr', '', ['class' => 'my-5']);
echo html_writer::tag('h4', get_string('dlq', 'local_integrationhub'), ['class' => 'mb-3']);
echo html_writer::tag('p', get_string('dlq_desc', 'local_integrationhub'), ['class' => 'text-muted']);

if (empty($dlqitems)) {
    echo html_writer::div(get_string('no_dlq_items', 'local_integrationhub'), 'alert alert-info');
} else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'style' => 'color: #212529 !important;']);
    echo '<thead class="table-dark"><tr>';
    echo '<th>' . get_string('col_event', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_error', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_created', 'local_integrationhub') . '</th>';
    if ($canmanage) {
        echo '<th>' . get_string('col_actions', 'local_integrationhub') . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($dlqitems as $item) {
        echo '<tr>';
        echo html_writer::tag('td', s($item->eventname));
        echo html_writer::tag('td', html_writer::tag('small', s($item->error_message), ['class' => 'text-danger']));
        echo html_writer::tag('td', userdate($item->timecreated));

        if ($canmanage) {
            echo '<td>';

            // View Payload Button (DLQ).
            $payloadview = $item->payload ? json_encode(json_decode($item->payload), JSON_PRETTY_PRINT) : '{}'; // Re-encode for pretty print

            echo html_writer::tag('button', '<i class="fa fa-code"></i>', [
                'class' => 'btn btn-sm btn-info me-1 ih-view-payload',
                'type' => 'button',
                'title' => get_string('view_payload', 'local_integrationhub'),
                'data-payload' => s($payloadview),
                'data-title' => get_string('payload_final', 'local_integrationhub') . ': ' . s($item->eventname),
            ]);

            $replayurl = new moodle_url($PAGE->url, ['action' => 'replay_dlq', 'dlqid' => $item->id, 'sesskey' => sesskey()]);
            echo html_writer::link($replayurl, '<i class="fa fa-play"></i>', [
                'class' => 'btn btn-sm btn-outline-success me-1',
                'title' => get_string('replay', 'local_integrationhub'),
            ]);

            $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete_dlq', 'dlqid' => $item->id, 'sesskey' => sesskey()]);
            echo html_writer::link($deleteurl, '<i class="fa fa-trash"></i>', [
                'class' => 'btn btn-sm btn-outline-danger',
                'title' => get_string('delete'),
                'onclick' => "return confirm('" . addslashes_js(get_string('dlq_delete_confirm', 'local_integrationhub')) . "');",
            ]);
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

// ---- MODAL FOR PAYLOAD (Via Moodle Core API) ----
// We use the AMD module local_integrationhub/queue
$PAGE->requires->js_call_amd('local_integrationhub/queue', 'init');

echo $OUTPUT->footer();
