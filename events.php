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
 * Sent events log view for the Integration Hub plugin.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrationhub/events.php'));
$PAGE->set_title(get_string('sent_events', 'local_integrationhub'));
$PAGE->set_heading(get_string('sent_events', 'local_integrationhub'));
$PAGE->set_pagelayout('admin');

// 1. Get configuration limit.
$limit = get_config('local_integrationhub', 'latest_events_limit') ?: 50;

// 2. Fetch logs (Outbound only).
// We use SQL to join service name and order by most recent.
$sql = "SELECT l.*, s.name AS service_name
        FROM {local_integrationhub_log} l
        LEFT JOIN {local_integrationhub_svc} s ON s.id = l.serviceid
        WHERE l.direction = :direction
        ORDER BY l.timecreated DESC";

$params = ['direction' => 'outbound'];
$logs = $DB->get_records_sql($sql, $params, 0, $limit);

// ---- OUTPUT ----
echo $OUTPUT->header();

// Tabs navigation (Standardized across pages).
echo html_writer::start_div('mb-4');
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs']);

// 1. Services
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/index.php'), get_string('services', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');

// 2. Rules
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/rules.php'), get_string('rules', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');

// 3. Queue
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/queue.php'), get_string('queue', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');

// 4. Sent Events (Active)
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/events.php'), get_string('sent_events', 'local_integrationhub'), ['class' => 'nav-link active']);
echo html_writer::end_tag('li');

echo html_writer::end_tag('ul');
echo html_writer::end_div();

// Content.
echo html_writer::tag('h4', get_string('latest_events_title', 'local_integrationhub', $limit), ['class' => 'mb-3']);

if (empty($logs)) {
    echo html_writer::div(get_string('no_events_logged', 'local_integrationhub'), 'alert alert-info');
}
else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo html_writer::start_tag('table', ['class' => 'table table-hover table-striped']);

    // Header.
    echo '<thead class="table-dark"><tr>';
    echo '<th>' . get_string('col_time', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_service', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_method', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_endpoint', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_status', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_latency_ms', 'local_integrationhub') . '</th>';
    echo '</tr></thead>';

    echo '<tbody>';
    foreach ($logs as $log) {
        echo '<tr>';

        // Time.
        echo '<td>' . userdate($log->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')) . '</td>';

        // Service.
        echo '<td>' . s($log->service_name ?? 'Unknown') . '</td>';

        // Method.
        $methodclass = 'badge bg-secondary';
        switch ($log->http_method) {
            case 'GET':
                $methodclass = 'badge bg-success';
                break;
            case 'POST':
                $methodclass = 'badge bg-primary';
                break;
            case 'PUT':
                $methodclass = 'badge bg-warning text-dark';
                break;
            case 'DELETE':
                $methodclass = 'badge bg-danger';
                break;
            case 'AMQP':
                $methodclass = 'badge bg-info text-dark';
                break;
        }
        echo '<td><span class="' . $methodclass . '">' . s($log->http_method) . '</span></td>';

        // Endpoint.
        echo '<td><code>' . s($log->endpoint) . '</code></td>';

        // Status.
        $statusclass = '';
        $text = $log->http_status;
        if ($log->success) {
            $statusclass = 'text-success fw-bold';
            if (!$text && $log->http_method === 'AMQP') {
                $text = 'OK';
            }
        }
        else {
            $statusclass = 'text-danger fw-bold';
            if (!$text) {
                $text = 'ERR';
            }
            // Show error in tooltip?
            if ($log->error_message) {
                $text .= ' <i class="fa fa-info-circle" title="' . s($log->error_message) . '"></i>';
            }
        }
        echo '<td class="' . $statusclass . '">' . $text . '</td>';

        // Latency.
        echo '<td>' . ($log->latency_ms !== null ? $log->latency_ms . 'ms' : '-') . '</td>';

        echo '</tr>';
    }
    echo '</tbody>';
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();