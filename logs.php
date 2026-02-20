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
 * Request logs viewer for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:view', $context);

// Filters.
$serviceid = optional_param('serviceid', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA); // 'success' or 'failure'
$page = optional_param('page', 0, PARAM_INT);
$perpage = 25;

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'clearlogs' && has_capability('local/integrationhub:manage', $context)) {
    require_sesskey(); // Checks sesskey in POST/GET and throws exception if missing.

    // Build specific deletion criteria.
    $conditions = [];
    if ($serviceid > 0) {
        $conditions['serviceid'] = $serviceid;
    }
    if ($status === 'success') {
        $conditions['success'] = 1;
    } elseif ($status === 'failure') {
        $conditions['success'] = 0;
    }

    if (!empty($conditions)) {
        $DB->delete_records('local_integrationhub_log', $conditions);
        // We use a generic message or constructing one based on filters would be overkill.
        \core\notification::success(get_string('logs_cleared', 'local_integrationhub'));
    } else {
        // No filters = Clear ALL.
        $DB->delete_records('local_integrationhub_log');
        \core\notification::success(get_string('logs_cleared', 'local_integrationhub'));
    }

    redirect($PAGE->url);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrationhub/logs.php', [
    'serviceid' => $serviceid, 'status' => $status,
]));
$PAGE->set_title(get_string('logs', 'local_integrationhub'));
$PAGE->set_heading(get_string('logs', 'local_integrationhub'));
$PAGE->set_pagelayout('admin');

// Get all services for filter dropdown.
$services = $DB->get_records('local_integrationhub_svc', null, 'name ASC');

// Build WHERE clause.
$where = [];
$params = [];

if ($serviceid > 0) {
    $where[] = 'l.serviceid = ?';
    $params[] = $serviceid;
}
if ($status === 'success') {
    $where[] = 'l.success = 1';
} elseif ($status === 'failure') {
    $where[] = 'l.success = 0';
}

$wheresql = '';
if (!empty($where)) {
    $wheresql = 'WHERE ' . implode(' AND ', $where);
}

// Count total.
$total = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {local_integrationhub_log} l {$wheresql}",
    $params
);

// Get paginated logs.
$sql = "SELECT l.*, s.name AS service_name
        FROM {local_integrationhub_log} l
        LEFT JOIN {local_integrationhub_svc} s ON s.id = l.serviceid
        {$wheresql}
        ORDER BY l.timecreated DESC";

$logs = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// ---- OUTPUT ----
echo $OUTPUT->header();

// Back to dashboard link.
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/local/integrationhub/index.php'),
    '← ' . get_string('dashboard', 'local_integrationhub'),
    ['class' => 'btn btn-outline-secondary btn-sm']
);
echo html_writer::end_div();

// Filters.
echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $PAGE->url->out_omit_querystring(),
    'class' => 'mb-4',
]);
echo '<div class="row align-items-end" style="gap: 16px;">';

// Service filter.
echo '<div class="col-auto" style="margin-right: 12px;">';
echo html_writer::tag(
    'label',
    get_string('col_name', 'local_integrationhub'),
    ['for' => 'filter-service', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']
);
echo html_writer::start_tag('select', [
    'name' => 'serviceid', 'id' => 'filter-service', 'class' => 'form-select',
]);
echo '<option value="0">' . get_string('all') . '</option>';
foreach ($services as $svc) {
    $selected = ($svc->id == $serviceid) ? 'selected' : '';
    echo "<option value='{$svc->id}' {$selected}>" . s($svc->name) . "</option>";
}
echo html_writer::end_tag('select');
echo '</div>';

// Status filter.
echo '<div class="col-auto" style="margin-right: 12px;">';
echo html_writer::tag(
    'label',
    get_string('col_success', 'local_integrationhub'),
    ['for' => 'filter-status', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']
);
echo html_writer::start_tag('select', [
    'name' => 'status', 'id' => 'filter-status', 'class' => 'form-select',
]);
$allsel = ($status === '') ? 'selected' : '';
$oksel = ($status === 'success') ? 'selected' : '';
$failsel = ($status === 'failure') ? 'selected' : '';
echo "<option value='' {$allsel}>" . get_string('all') . "</option>";
echo "<option value='success' {$oksel}>" . get_string('result_success', 'local_integrationhub') . "</option>";
echo "<option value='failure' {$failsel}>" . get_string('result_failure', 'local_integrationhub') . "</option>";
echo html_writer::end_tag('select');
echo '</div>';

// Submit.
echo '<div class="col-auto">';
echo html_writer::tag('button', get_string('search'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo '</div>';

echo '</div>'; // .row
echo html_writer::end_tag('form');

// Clear Logs Button (Admin only) — OUTSIDE the filter form to avoid nested form issues.
if (has_capability('local/integrationhub:manage', $context)) {
    echo '<div class="mb-4 text-end">';
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url->out_omit_querystring(),
        'style' => 'display: inline;',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'clearlogs']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'serviceid', 'value' => $serviceid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'status', 'value' => $status]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::tag('button', '<i class="fa fa-trash"></i> ' . get_string('clearlogs', 'local_integrationhub'), [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => "return confirm('" . addslashes_js(get_string('clearlogs_confirm', 'local_integrationhub')) . "');",
    ]);
    echo html_writer::end_tag('form');
    echo '</div>';
}

// CSS Fix for Moodle Themes (Force dark text on hover).
echo html_writer::tag('style', "
    .table-hover tbody tr:hover { color: #212529 !important; }
    .table-striped tbody tr:nth-of-type(odd) { color: #212529 !important; }
");

// Summary.
echo html_writer::tag(
    'p',
    "<strong>{$total}</strong> " . get_string('logs', 'local_integrationhub'),
    ['class' => 'text-muted']
);

// Logs table.
if (empty($logs)) {
    echo html_writer::div(get_string('nologs', 'local_integrationhub'), 'alert alert-info');
} else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    // Force text-dark to avoid theme white-text issues on some backgrounds
    echo html_writer::start_tag('table', ['class' => 'table table-sm table-striped table-hover', 'style' => 'color: #212529 !important;']);

    // Header.
    echo '<thead class="table-dark"><tr>';
    $headers = ['col_direction', 'col_name', 'col_endpoint', 'col_method', 'col_status', 'col_latency_ms',
        'col_attempts', 'col_success', 'col_error', 'col_time'];
    foreach ($headers as $h) {
        echo html_writer::tag('th', get_string($h, 'local_integrationhub'));
    }
    echo '</tr></thead>';

    // Body.
    echo '<tbody>';
    foreach ($logs as $log) {
        echo '<tr>';

        // Direction badge.
        $dir = $log->direction ?? 'outbound';
        if ($dir === 'inbound') {
            echo html_writer::tag('td', html_writer::tag('span', '⬇ ' . get_string('direction_inbound', 'local_integrationhub'), ['class' => 'badge bg-info text-dark']));
        } else {
            echo html_writer::tag('td', html_writer::tag('span', '⬆ ' . get_string('direction_outbound', 'local_integrationhub'), ['class' => 'badge bg-secondary']));
        }

        // Service name.
        echo html_writer::tag('td', s($log->service_name ?? '(deleted)'));

        // Endpoint.
        echo html_writer::tag('td', html_writer::tag('code', s($log->endpoint)));

        // Method badge.
        $methodclass = 'badge bg-secondary';
        if ($log->http_method === 'GET') {
            $methodclass = 'badge bg-success';
        }
        if ($log->http_method === 'POST') {
            $methodclass = 'badge bg-primary';
        }
        if ($log->http_method === 'PUT') {
            $methodclass = 'badge bg-warning text-dark';
        }
        if ($log->http_method === 'DELETE') {
            $methodclass = 'badge bg-danger';
        }
        if ($log->http_method === 'AMQP') {
            $methodclass = 'badge bg-info text-dark';
        }
        echo html_writer::tag('td', html_writer::tag('span', $log->http_method, ['class' => $methodclass]));

        // HTTP status.
        $statclass = '';
        if ($log->http_status >= 200 && $log->http_status < 300) {
            $statclass = 'text-success fw-bold';
        } elseif ($log->http_status >= 400) {
            $statclass = 'text-danger fw-bold';
        }
        echo html_writer::tag('td', html_writer::tag('span', $log->http_status ?? '—', ['class' => $statclass]));

        // Latency.
        echo html_writer::tag('td', ($log->latency_ms !== null) ? $log->latency_ms . ' ms' : '—');

        // Attempts.
        $attclass = ($log->attempt_count > 1) ? 'text-warning fw-bold' : '';
        echo html_writer::tag('td', html_writer::tag('span', $log->attempt_count, ['class' => $attclass]));

        // Success/Failure badge.
        if ($log->success) {
            echo html_writer::tag('td', html_writer::tag(
                'span',
                get_string('result_success', 'local_integrationhub'),
                ['class' => 'badge bg-success']
            ));
        } else {
            echo html_writer::tag('td', html_writer::tag(
                'span',
                get_string('result_failure', 'local_integrationhub'),
                ['class' => 'badge bg-danger']
            ));
        }

        // Error message.
        $errmsg = $log->error_message ? s($log->error_message) : '—';
        echo html_writer::tag('td', html_writer::tag('small', $errmsg, [
            'class' => $log->error_message ? 'text-danger' : 'text-muted',
        ]));

        // Time.
        echo html_writer::tag('td', html_writer::tag(
            'small',
            userdate($log->timecreated, get_string('strftimedatetimeshort', 'core_langconfig'))
        ));

        echo '</tr>';
    }
    echo '</tbody></table></div>';

    // Pagination.
    $baseurl = new moodle_url('/local/integrationhub/logs.php', [
        'serviceid' => $serviceid, 'status' => $status,
    ]);
    echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
