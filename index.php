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
 * Dashboard — main page for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_integrationhub\service\registry as service_registry;
use local_integrationhub\service\circuit_breaker;

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:view', $context);

$canmanage = has_capability('local/integrationhub:manage', $context);

// Handle actions.
$action   = optional_param('action', '', PARAM_ALPHANUMEXT);
$serviceid = optional_param('serviceid', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrationhub/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_integrationhub'));
$PAGE->set_heading(get_string('pluginname', 'local_integrationhub'));
$PAGE->set_pagelayout('admin');

// Process form submission.
if ($canmanage && $action === 'save' && confirm_sesskey()) {
    $data = new stdClass();
    $data->name                = required_param('name', PARAM_TEXT);
    $data->type                = optional_param('type', 'rest', PARAM_ALPHA);
    $data->base_url            = required_param('base_url', PARAM_URL);
    $data->auth_type           = required_param('auth_type', PARAM_ALPHA);
    $data->auth_token          = optional_param('auth_token', '', PARAM_RAW);
    $data->timeout             = optional_param('timeout', 5, PARAM_INT);
    $data->max_retries         = optional_param('max_retries', 3, PARAM_INT);
    $data->retry_backoff       = optional_param('retry_backoff', 1, PARAM_INT);
    $data->cb_failure_threshold = optional_param('cb_failure_threshold', 5, PARAM_INT);
    $data->cb_cooldown         = optional_param('cb_cooldown', 30, PARAM_INT);
    $data->response_queue      = optional_param('response_queue', '', PARAM_ALPHANUMEXT);

    if ($serviceid > 0) {
        service_registry::update_service($serviceid, $data);
        \core\notification::success(get_string('serviceupdated', 'local_integrationhub', $data->name));
    } else {
        service_registry::create_service($data);
        \core\notification::success(get_string('servicecreated', 'local_integrationhub', $data->name));
    }
    redirect($PAGE->url);
}

// Process delete.
if ($canmanage && $action === 'delete' && $serviceid > 0 && confirm_sesskey()) {
    if ($confirm) {
        $service = service_registry::get_service_by_id($serviceid);
        service_registry::delete_service($serviceid);
        \core\notification::success(get_string('servicedeleted', 'local_integrationhub', $service->name));
        redirect($PAGE->url);
    }
}

// Process circuit reset.
if ($canmanage && $action === 'resetcircuit' && $serviceid > 0 && confirm_sesskey()) {
    $service = service_registry::get_service_by_id($serviceid);
    $cb = circuit_breaker::from_service($service);
    $cb->reset();
    \core\notification::success(get_string('circuitreset', 'local_integrationhub', $service->name));
    redirect($PAGE->url);
}

// Process reset all circuits.
if ($canmanage && $action === 'resetall' && confirm_sesskey()) {
    $services = service_registry::get_all_services();
    foreach ($services as $svc) {
        $cb = circuit_breaker::from_service($svc);
        $cb->reset();
    }
    \core\notification::success(get_string('allcircuitsreset', 'local_integrationhub'));
    redirect($PAGE->url);
}

// Load data.
$services = service_registry::get_all_services();

// Calculate stats for each service.
foreach ($services as $service) {
    $cb = circuit_breaker::from_service($service);
    $service->circuit_state = $cb->get_state_label();
    $cbstate = $cb->get_state();
    $service->circuit_state_raw = strtolower($cbstate->state);

    // If OPEN, append retry info.
    if ($service->circuit_state_raw === 'open') {
        $retrytime = $cbstate->timemodified + $service->cb_cooldown;
        $remaining = $retrytime - time();
        if ($remaining > 0) {
            $service->circuit_state .= ' <br><small class="text-white-50">Retry in ' . $remaining . 's</small>';
        }
    }

    // Get average latency from last 50 requests.
    $service->avg_latency = $DB->get_field_sql(
        "SELECT AVG(latency_ms) FROM {local_integrationhub_log}
         WHERE serviceid = ? AND success = 1
         ORDER BY timecreated DESC",
        [$service->id]
    );

    // Get recent error count (last 24h).
    $since = time() - 86400;
    $service->recent_errors = $DB->count_records_select(
        'local_integrationhub_log',
        'serviceid = ? AND success = 0 AND timecreated > ?',
        [$service->id, $since]
    );
}

// Prepare edit data if editing.
$editservice = null;
if ($canmanage && $action === 'edit' && $serviceid > 0) {
    $editservice = service_registry::get_service_by_id($serviceid);
}

// ---- CHARTS DATA PREPARATION ----
// 1. Status Distribution (Global).
$statusstats = $DB->get_records_sql(
    "SELECT success, COUNT(*) as count 
     FROM {local_integrationhub_log} 
     GROUP BY success"
);
$successcount = 0;
$failcount = 0;
foreach ($statusstats as $stat) {
    if ($stat->success == 1) $successcount = (int)$stat->count;
    else $failcount = (int)$stat->count;
}

// 2. Latency Trend (Last 200 requests).
$logs = $DB->get_records('local_integrationhub_log', [], 'timecreated DESC', '*', 0, 200);
$chartlabels = [];
$chartdata = [];

// Process in reverse to get chronological order.
foreach (array_reverse($logs) as $log) {
    if ($log->latency_ms === null) continue;
    $chartlabels[] = date('H:i', $log->timecreated);
    $chartdata[] = (int)$log->latency_ms;
}

// Prepare data for JS.
$chartdata_js = [
    'success' => $successcount,
    'fail' => $failcount,
    'labels' => $chartlabels,
    'latency' => $chartdata
];

// ---- OUTPUT ----
echo $OUTPUT->header();

// Tabs navigation.
echo html_writer::start_div('mb-4');
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs']);
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/index.php'), get_string('services', 'local_integrationhub'), ['class' => 'nav-link active']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/rules.php'), get_string('rules', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/queue.php'), get_string('queue', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::end_tag('ul');
echo html_writer::end_div();

// Charts Section.
echo html_writer::start_div('row mb-4');

// Chart 1: Status.
echo html_writer::start_div('col-md-4');
echo html_writer::start_div('card');
echo html_writer::tag('div', get_string('integrationstatus', 'local_integrationhub'), ['class' => 'card-header fw-bold']);
echo html_writer::start_div('card-body', ['style' => 'height: 300px; position: relative;']);
echo '<canvas id="ih-chart-status"></canvas>';
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Chart 2: Latency.
echo html_writer::start_div('col-md-8');
echo html_writer::start_div('card');
echo html_writer::tag('div', get_string('latencytrend', 'local_integrationhub'), ['class' => 'card-header fw-bold']);
echo html_writer::start_div('card-body', ['style' => 'height: 300px; position: relative;']);
echo '<canvas id="ih-chart-latency"></canvas>';
echo html_writer::end_div(); // Close card-body
echo html_writer::end_div(); // Close card
echo html_writer::end_div(); // Close col-md-8
echo html_writer::end_div(); // Close Row (Charts Section)

// Include Chart.js (Local) with UMD/AMD Hack.
echo '<script>
    var _old_define = window.define;
    window.define = null;
</script>';
echo '<script src="' . new moodle_url('/local/integrationhub/assets/min/chart.umd.min.js') . '"></script>';
echo '<script>
    window.define = _old_define;
</script>';

// Chart.js local file is loaded below.

// Action buttons.
echo html_writer::start_div('row mb-4');
echo html_writer::start_div('col-12 d-flex', ['style' => 'gap: 12px;']);
if ($canmanage) {
    echo html_writer::tag('button', get_string('addservice', 'local_integrationhub'), [
        'class' => 'btn btn-primary',
        'id'    => 'ih-btn-add',
        'type'  => 'button',
    ]);
    
    $resetallurl = new moodle_url($PAGE->url, ['action' => 'resetall', 'sesskey' => sesskey()]);
    echo html_writer::link($resetallurl, '<i class="fa fa-refresh"></i> ' . get_string('resetallcircuits', 'local_integrationhub'), 
        ['class' => 'btn btn-outline-warning']);
}
echo html_writer::link(
    new moodle_url('/local/integrationhub/logs.php'),
    '<i class="fa fa-list-alt"></i> ' . get_string('viewlogs', 'local_integrationhub'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div(); // col-12
echo html_writer::end_div(); // row

// Force a visual break between buttons and the form/table.
// Removed aggressive spacer.

// Inline form (hidden by default, shown via JS or when editing).
$showform = ($canmanage && ($action === 'add' || $editservice));
$formclass = $showform ? '' : 'd-none';

echo html_writer::start_div("card mb-4 {$formclass}", ['id' => 'ih-service-form']);
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', $editservice
    ? get_string('editservice', 'local_integrationhub')
    : get_string('addservice', 'local_integrationhub'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false),
    'id'     => 'ih-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'serviceid',
    'value' => $editservice ? $editservice->id : 0, 'id' => 'ih-serviceid']);

// Form fields.
$fields = [
    ['name', 'servicename', 'text', $editservice->name ?? '', true],
    ['base_url', 'baseurl', 'url', $editservice->base_url ?? '', true],
    ['auth_token', 'authtoken', 'password', $editservice->auth_token ?? '', false],
    ['response_queue', 'response_queue', 'text', $editservice->response_queue ?? '', false],
    ['timeout', 'timeout', 'number', $editservice->timeout ?? 5, false],
    ['max_retries', 'maxretries', 'number', $editservice->max_retries ?? 3, false],
    ['retry_backoff', 'retrybackoff', 'number', $editservice->retry_backoff ?? 1, false],
    ['cb_failure_threshold', 'cbfailurethreshold', 'number', $editservice->cb_failure_threshold ?? 5, false],
    ['cb_cooldown', 'cbcooldown', 'number', $editservice->cb_cooldown ?? 30, false],
];

echo '<div class="row">';

// Service type select.
echo '<div class="col-md-6 mb-3">';
echo html_writer::tag('label', get_string('col_type', 'local_integrationhub'), [
    'for' => 'ih-type', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;',
]);
echo html_writer::start_tag('select', [
    'name' => 'type', 'id' => 'ih-type', 'class' => 'form-select',
]);
$selectedrest = (($editservice->type ?? 'rest') === 'rest') ? 'selected' : '';
$selectedamqp = (($editservice->type ?? '') === 'amqp') ? 'selected' : '';
$selectedsoap = (($editservice->type ?? '') === 'soap') ? 'selected' : '';
echo "<option value='rest' {$selectedrest}>REST</option>";
echo "<option value='amqp' {$selectedamqp}>AMQP (RabbitMQ)</option>";
echo "<option value='soap' {$selectedsoap}>SOAP (Future)</option>";
echo html_writer::end_tag('select');
echo '</div>';

// Auth type select.
echo '<div class="col-md-6 mb-3">';
echo html_writer::tag('label', get_string('authtype', 'local_integrationhub'), [
    'for' => 'ih-auth_type', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;',
]);
echo html_writer::start_tag('select', [
    'name' => 'auth_type', 'id' => 'ih-auth_type', 'class' => 'form-select',
]);
$selectedbearer = (($editservice->auth_type ?? 'bearer') === 'bearer') ? 'selected' : '';
$selectedapikey = (($editservice->auth_type ?? '') === 'apikey') ? 'selected' : '';
echo "<option value='bearer' {$selectedbearer}>" . get_string('authtype_bearer', 'local_integrationhub') . "</option>";
echo "<option value='apikey' {$selectedapikey}>" . get_string('authtype_apikey', 'local_integrationhub') . "</option>";
echo html_writer::end_tag('select');
echo '</div>';

// JavaScript to toggle visibility.
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const typeSelect = document.getElementById("ih-type");
    const authTypeContainer = document.getElementById("ih-auth_type").closest(".col-md-6");
    const authTypeLabel = authTypeContainer.querySelector("label");

    function toggleAuthType() {
        if (typeSelect.value === "amqp") {
            authTypeContainer.classList.add("d-none");
        } else {
            authTypeContainer.classList.remove("d-none");
        }
    }

    typeSelect.addEventListener("change", toggleAuthType);
    toggleAuthType();
});
</script>';

// AMQP Connection Builder (Conditional Visibility via JS).
echo '<div id="ih-amqp-builder" class="col-12 d-none mb-3">';
echo '<div class="card bg-light border-info"><div class="card-body">';
echo '<h6 class="card-title text-info"><i class="fa fa-magic"></i> ' . get_string('amqp_builder', 'local_integrationhub') . '</h6>';
echo '<div class="row">';
$amqpfields = [
    ['amqp_host', 'amqp_host', 'text', 'localhost', 'localhost'],
    ['amqp_port', 'amqp_port', 'number', '5672', '5672'],
    ['amqp_user', 'amqp_user', 'text', 'guest', 'guest'],
    ['amqp_pass', 'amqp_pass', 'password', 'guest', ''],
    ['amqp_vhost', 'amqp_vhost', 'text', '/', '/'],
];
foreach ($amqpfields as $af) {
    echo '<div class="col-md-2 mb-2">';
    echo html_writer::tag('label', get_string($af[1], 'local_integrationhub'), ['class' => 'small', 'style' => 'display:block; margin-bottom:2px;']);
    echo html_writer::empty_tag('input', [
        'type' => $af[2], 'id' => "ih-{$af[0]}", 'value' => $af[3], 'class' => 'form-control form-control-sm ih-amqp-sync', 'placeholder' => $af[4]
    ]);
    echo '</div>';
}
echo '</div>'; // .row
echo '</div></div>';
echo '</div>';

foreach ($fields as $field) {
    [$fname, $stringkey, $type, $value, $required] = $field;
    echo '<div class="col-md-6 mb-3">';
    echo html_writer::tag('label', get_string($stringkey, 'local_integrationhub'), [
        'for' => "ih-{$fname}", 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;',
    ]);
    $attrs = [
        'type'  => $type,
        'name'  => $fname,
        'id'    => "ih-{$fname}",
        'value' => $value,
        'class' => 'form-control',
    ];
    if ($required) {
        $attrs['required'] = 'required';
    }
    if ($type === 'number') {
        $attrs['min'] = '0';
    }
    echo html_writer::empty_tag('input', $attrs);
    if ($fname === 'base_url') {
        echo html_writer::tag('div', get_string('base_url_help', 'local_integrationhub'), [
            'class' => 'form-text text-muted', 'id' => 'ih-base_url-help'
        ]);
    }
    if ($fname === 'response_queue') {
        echo html_writer::tag('div', get_string('response_queue_help', 'local_integrationhub'), ['class' => 'form-text text-muted']);
    }
    echo '</div>';
}

echo '</div>'; // .row

// Form buttons.
echo html_writer::start_div('d-flex');
echo html_writer::tag('button', get_string('saveservice', 'local_integrationhub'), [
    'type' => 'submit', 'class' => 'btn btn-success me-2', // Added me-2 for spacing
]);
echo html_writer::tag('button', get_string('cancel', 'local_integrationhub'), [
    'type' => 'button', 'class' => 'btn btn-secondary', 'id' => 'ih-btn-cancel',
]);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div(); // .card-body
echo html_writer::end_div(); // .card #ih-service-form

// Another spacer if form is hidden/shown, just to be safe.
// spacer removed.

// Services table.
echo html_writer::tag('h4', get_string('services', 'local_integrationhub'), ['class' => 'mb-3', 'style' => 'clear: both;']);

if (empty($services)) {
    echo html_writer::div(get_string('noservices', 'local_integrationhub'), 'alert alert-info');
} else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    // Force text-dark to avoid theme white-text issues
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'id' => 'ih-services-table', 'style' => 'color: #212529 !important;']);

    // Header.
    echo '<thead class="table-dark"><tr>';
    $headers = ['col_name', 'col_type', 'col_baseurl', 'col_authtype', 'col_circuit', 'col_latency',
                'col_errors', 'col_enabled'];
    if ($canmanage) {
        $headers[] = 'col_actions';
    }
    foreach ($headers as $h) {
        echo html_writer::tag('th', get_string($h, 'local_integrationhub'));
    }
    echo '</tr></thead>';

    // Body.
    echo '<tbody>';
    foreach ($services as $svc) {
        echo '<tr>';
        echo html_writer::tag('td', s($svc->name));
        $typelabel = strtoupper($svc->type ?? 'rest');
        $typeclass = ($svc->type ?? 'rest') === 'rest' ? 'badge bg-secondary' : 'badge bg-dark';
        echo html_writer::tag('td', html_writer::tag('span', $typelabel, ['class' => $typeclass]));
        echo html_writer::tag('td', html_writer::tag('code', s($svc->base_url)));

        // Auth type badge.
        $authbadge = ($svc->auth_type === 'bearer') ? 'badge bg-primary' : 'badge bg-info';
        echo html_writer::tag('td', html_writer::tag('span', strtoupper(s($svc->auth_type)), ['class' => $authbadge]));

        // Circuit state badge.
        $cbclass = 'badge bg-success';
        if ($svc->circuit_state_raw === 'open') {
            $cbclass = 'badge bg-danger';
        } else if ($svc->circuit_state_raw === 'halfopen') {
            $cbclass = 'badge bg-warning text-dark';
        }
        echo html_writer::tag('td', html_writer::tag('span', $svc->circuit_state, ['class' => $cbclass]));

        // Latency.
        $latency = $svc->avg_latency ? round($svc->avg_latency) . ' ms' : '—';
        echo html_writer::tag('td', $latency);

        // Recent errors.
        $errclass = $svc->recent_errors > 0 ? 'text-danger fw-bold' : 'text-muted';
        echo html_writer::tag('td', html_writer::tag('span', $svc->recent_errors, ['class' => $errclass]));

        // Enabled status.
        $statusclass = $svc->enabled ? 'badge bg-success' : 'badge bg-secondary';
        $statuslabel = $svc->enabled
            ? get_string('status_active', 'local_integrationhub')
            : get_string('status_disabled', 'local_integrationhub');
        echo html_writer::tag('td', html_writer::tag('span', $statuslabel, ['class' => $statusclass]));

        // Actions.
        if ($canmanage) {
            echo '<td class="text-nowrap">';

            // Logs for this service.
            $logsurl = new moodle_url('/local/integrationhub/logs.php', ['serviceid' => $svc->id]);
            echo html_writer::link($logsurl, '<i class="fa fa-bar-chart"></i> ',
                ['class' => 'btn btn-sm btn-outline-info me-1', 'title' => get_string('viewlogs', 'local_integrationhub')]);

            // Edit button.
            $editurl = new moodle_url($PAGE->url, ['action' => 'edit', 'serviceid' => $svc->id]);
            echo html_writer::link($editurl, '<i class="fa fa-pencil"></i> ',
                ['class' => 'btn btn-sm btn-outline-primary me-1', 'title' => get_string('editservice', 'local_integrationhub')]);

            // Delete button.
            $deleteurl = new moodle_url($PAGE->url, [
                'action' => 'delete', 'serviceid' => $svc->id,
                'confirm' => 1, 'sesskey' => sesskey(),
            ]);
            echo html_writer::link($deleteurl, '<i class="fa fa-trash"></i> ',
                ['class' => 'btn btn-sm btn-outline-danger me-1',
                 'title' => get_string('deleteservice', 'local_integrationhub'),
                 'onclick' => "return confirm('" .
                    addslashes_js(get_string('deleteconfirm', 'local_integrationhub', $svc->name)) . "');"]);

            // Reset circuit (only if not closed).
            if ($svc->circuit_state_raw !== 'closed') {
                $reseturl = new moodle_url($PAGE->url, [
                    'action' => 'resetcircuit', 'serviceid' => $svc->id, 'sesskey' => sesskey(),
                ]);
                echo html_writer::link($reseturl, '<i class="fa fa-refresh"></i> ',
                    ['class' => 'btn btn-sm btn-outline-warning',
                     'title' => get_string('resetcircuit', 'local_integrationhub')]);
            }

            echo '</td>';
        }

        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

// Delete confirmation dialog (if pending).
if ($action === 'delete' && $serviceid > 0 && !$confirm && $canmanage) {
    try {
        $service = service_registry::get_service_by_id($serviceid);
        $confirmurl = new moodle_url($PAGE->url, [
            'action' => 'delete', 'serviceid' => $serviceid,
            'confirm' => 1, 'sesskey' => sesskey(),
        ]);
        $cancelurl = $PAGE->url;
        echo $OUTPUT->confirm(
            get_string('deleteconfirm', 'local_integrationhub', $service->name),
            $confirmurl,
            $cancelurl
        );
    } catch (\Exception $e) {
        // Service not found, just redirect.
        redirect($PAGE->url);
    }
}

// Call AMD Module.
$PAGE->requires->js_call_amd('local_integrationhub/dashboard', 'init', [
    $chartdata_js,
    [
        'success'    => get_string('success', 'local_integrationhub'),
        'failure'    => get_string('failure', 'local_integrationhub'),
        'avglatency' => get_string('avglatency', 'local_integrationhub'),
        'url_help_rest' => get_string('url_help_rest', 'local_integrationhub'),
        'url_help_amqp' => get_string('url_help_amqp', 'local_integrationhub'),
    ]
]);

echo $OUTPUT->footer();
