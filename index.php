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
    $data->base_url            = required_param('base_url', PARAM_RAW_TRIMMED);
    $data->auth_type           = required_param('auth_type', PARAM_ALPHA);
    $data->auth_token          = optional_param('auth_token', '', PARAM_RAW);
    $data->timeout             = optional_param('timeout', 5, PARAM_INT);
    $data->max_retries         = optional_param('max_retries', 3, PARAM_INT);
    $data->retry_backoff       = optional_param('retry_backoff', 1, PARAM_INT);
    $data->cb_failure_threshold = optional_param('cb_failure_threshold', 5, PARAM_INT);
    $data->cb_cooldown         = optional_param('cb_cooldown', 30, PARAM_INT);
    $data->response_queue      = optional_param('response_queue', '', PARAM_ALPHANUMEXT);

    // SERVER-SIDE URL RECONSTRUCTION FOR AMQP
    // This ensures that even if JS fails to update the hidden base_url field,
    // we construct the correct URL from the individual fields.
    if ($data->type === 'amqp') {
        $amqp_host = optional_param('amqp_host', 'localhost', PARAM_HOST);
        $amqp_port = optional_param('amqp_port', 5672, PARAM_INT);
        $amqp_user = optional_param('amqp_user', 'guest', PARAM_TEXT);
        $amqp_pass = optional_param('amqp_pass', 'guest', PARAM_RAW);
        $amqp_vhost = optional_param('amqp_vhost', '/', PARAM_RAW); // Allow slash

        $amqp_exchange = optional_param('ih-amqp_exchange', '', PARAM_TEXT); // Note ID prefix in form
        // Wait, the input IDs in form are 'ih-amqp_exchange' but names act as keys?
        // Let's check the form generation code below.
        // The helper loop uses $af[0] as ID. But names?
        // line 403: 'id' => "ih-{$af[0]}"
        // It does NOT specify 'name' explicitly in the array unpack loop above?
        // Ah, look at line 469: 'name' => $fname. BUT that's for normal fields.
        // The AMQP loop (line 398) uses:
        // echo html_writer::empty_tag('input', ['type' => $af[2], 'id' => "ih-{$af[0]}", ...]);
        // IT DOES NOT HAVE A NAME ATTRIBUTE!!!!
        // This explains EVERYTHING. The PHP script CANNOT receive these values because they have no name attribute!
        // The JS was the *only* thing making it work by updating the hidden base_url.
        // FIX: I must add 'name' attributes to the AMQP builder inputs in the form generation code first.
    }

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
    if ($stat->success == 1) {
        $successcount = (int)$stat->count;
    } else {
        $failcount = (int)$stat->count;
    }
}

// 2. Latency Trend (Last 200 requests).
$logs = $DB->get_records('local_integrationhub_log', [], 'timecreated DESC', '*', 0, 200);
$chartlabels = [];
$chartdata = [];

// Process in reverse to get chronological order.
foreach (array_reverse($logs) as $log) {
    if ($log->latency_ms === null) {
        continue;
    }
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
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/events.php'), get_string('sent_events', 'local_integrationhub'), ['class' => 'nav-link']);
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
    echo html_writer::link(
        $resetallurl,
        '<i class="fa fa-refresh"></i> ' . get_string('resetallcircuits', 'local_integrationhub'),
        ['class' => 'btn btn-outline-warning']
    );
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
    ['base_url', 'baseurl', 'text', $editservice->base_url ?? '', true],
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
echo "<option value='soap' {$selectedsoap}>SOAP</option>";
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
    const baseUrlContainer = document.querySelector(".ih-base-url-container");
    const amqpBuilder = document.getElementById("ih-amqp-builder");

    function toggleUi() {
        if (typeSelect.value === "amqp") {
            authTypeContainer.classList.add("d-none");
            baseUrlContainer.classList.add("d-none");
            amqpBuilder.classList.remove("d-none");
        } else {
            authTypeContainer.classList.remove("d-none");
            baseUrlContainer.classList.remove("d-none");
            amqpBuilder.classList.add("d-none");
        }
    }

    typeSelect.addEventListener("change", toggleUi);
    toggleUi();
});
</script>';

// AMQP Connection Builder (Conditional Visibility via JS).
echo '<div id="ih-amqp-builder" class="col-12 d-none mb-3">';
echo '<div class="card bg-light border-info"><div class="card-body">';
echo '<h6 class="card-title text-info"><i class="fa fa-magic"></i> ' . get_string('amqp_builder', 'local_integrationhub') . '</h6>';
echo '<div class="row">';

// Parse existing URL if editing AMQP
$amqpparts = [
    'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'pass' => 'guest', 'vhost' => '/',
    'exchange' => '', 'routing_key' => '', 'queue_declare' => '', 'dlq' => ''
];

if (!empty($editservice) && ($editservice->type === 'amqp')) {
    $parsed = parse_url($editservice->base_url);
    $amqpparts['host'] = $parsed['host'] ?? 'localhost';
    $amqpparts['port'] = $parsed['port'] ?? 5672;
    $amqpparts['user'] = $parsed['user'] ?? 'guest';
    $amqpparts['pass'] = $parsed['pass'] ?? 'guest';

    // Decoded vhost for display
    $path = isset($parsed['path']) ? $parsed['path'] : '/';
    if ($path !== '/' && strpos($path, '/') === 0) {
        $path = substr($path, 1);
    }
    $amqpparts['vhost'] = urldecode($path);
    if ($amqpparts['vhost'] === '') {
        $amqpparts['vhost'] = '/';
    }

    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
        $amqpparts['exchange'] = $query['exchange'] ?? '';
        $amqpparts['routing_key'] = $query['routing_key'] ?? '';
        $amqpparts['queue_declare'] = $query['queue_declare'] ?? '';
        $amqpparts['dlq'] = $query['dlq'] ?? '';
    }
}

// Core Connection
$amqpfields = [
    ['amqp_host', 'amqp_host', 'text', $amqpparts['host'], 'localhost', 'col-md-3'],
    ['amqp_port', 'amqp_port', 'number', $amqpparts['port'], '5672', 'col-md-2'],
    ['amqp_user', 'amqp_user', 'text', $amqpparts['user'], 'guest', 'col-md-2'],
    ['amqp_pass', 'amqp_pass', 'password', $amqpparts['pass'], '', 'col-md-2'], // Password might be masked in future
    ['amqp_vhost', 'amqp_vhost', 'text', $amqpparts['vhost'], '/', 'col-md-3'],
];
foreach ($amqpfields as $af) {
    // array unpack: id, langstr, type, val, placeholder, colclass
    $col = $af[5] ?? 'col-md-2';
    echo '<div class="' . $col . ' mb-2">';
    echo html_writer::tag('label', get_string($af[1], 'local_integrationhub'), ['class' => 'small', 'style' => 'display:block; margin-bottom:2px;']);
    echo html_writer::empty_tag('input', [
        'type' => $af[2],
        'name' => $af[0], // Add NAME attribute so PHP receives it!
        'id' => "ih-{$af[0]}",
        'value' => $af[3],
        'class' => 'form-control form-control-sm ih-amqp-sync',
        'placeholder' => $af[4]
    ]);
    echo '</div>';
}

echo '</div>'; // .row (Connection)

    // Advanced: Queue & DLQ
    echo '<div class="row mt-2 border-top pt-2">';

    // Exchange
    echo '<div class="col-md-3">';
    echo html_writer::tag('label', get_string('amqp_exchange', 'local_integrationhub'), ['class' => 'small fw-bold']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'ih-amqp_exchange', // Manual name
        'id' => 'ih-amqp_exchange',
        'class' => 'form-control form-control-sm ih-amqp-sync',
        'placeholder' => '(Default)',
        'value' => $amqpparts['exchange']
    ]);
    echo '</div>';

    // Routing Key
    echo '<div class="col-md-3">';
    echo html_writer::tag('label', get_string('amqp_routing_key_default', 'local_integrationhub'), ['class' => 'small fw-bold']);
    echo html_writer::tag('i', '', [
        'class' => 'fa fa-question-circle text-muted ms-1',
        'title' => get_string('amqp_routing_key_help', 'local_integrationhub'),
        'data-toggle' => 'tooltip'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'ih-amqp_routing_key', // Manual name
        'id' => 'ih-amqp_routing_key',
        'class' => 'form-control form-control-sm ih-amqp-sync',
        'placeholder' => 'my.routing.key',
        'value' => $amqpparts['routing_key']
    ]);
    echo '</div>';

    // Queue Declare
    echo '<div class="col-md-3">';
    echo html_writer::tag('label', get_string('amqp_queue_declare', 'local_integrationhub'), ['class' => 'small fw-bold']);
    echo html_writer::tag('i', '', [
        'class' => 'fa fa-question-circle text-muted ms-1',
        'title' => get_string('amqp_queue_help', 'local_integrationhub'),
        'data-toggle' => 'tooltip'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'ih-amqp_queue_declare', // Manual name
        'id' => 'ih-amqp_queue_declare',
        'class' => 'form-control form-control-sm ih-amqp-sync',
        'placeholder' => 'my_queue',
        'value' => $amqpparts['queue_declare']
    ]);
    echo '</div>';


    // DLQ
    echo '<div class="col-md-3">';
    echo html_writer::tag('label', get_string('amqp_dlq', 'local_integrationhub'), ['class' => 'small fw-bold']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'ih-amqp_dlq', // Manual name
        'id' => 'ih-amqp_dlq',
        'class' => 'form-control form-control-sm ih-amqp-sync',
        'placeholder' => 'my_dlq',
        'value' => $amqpparts['dlq']
    ]);
    echo '</div>';

    echo '</div>'; // .row (Queues)

    echo '</div></div>';
    echo '</div>';

    foreach ($fields as $field) {
        [$fname, $stringkey, $type, $value, $required] = $field;
        $divclass = 'col-md-6 mb-3';

        // Hide base_url container if AMQP (handled by JS via ID)
        if ($fname === 'base_url') {
            $divclass .= ' ih-base-url-container'; // Marker class
        }

        echo '<div class="' . $divclass . '">';
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
            $typeclass = 'badge bg-secondary';
            if (($svc->type ?? 'rest') === 'amqp') {
                $typeclass = 'badge bg-info text-dark';
            } elseif (($svc->type ?? 'rest') === 'soap') {
                $typeclass = 'badge bg-warning text-dark';
            }
            echo html_writer::tag('td', html_writer::tag('span', $typelabel, ['class' => $typeclass]));
            echo html_writer::tag('td', html_writer::tag('code', s($svc->base_url)));

            // Auth type badge.
            $authbadge = ($svc->auth_type === 'bearer') ? 'badge bg-primary' : 'badge bg-info';
            echo html_writer::tag('td', html_writer::tag('span', strtoupper(s($svc->auth_type)), ['class' => $authbadge]));

            // Circuit state badge.
            $cbclass = 'badge bg-success';
            if ($svc->circuit_state_raw === 'open') {
                $cbclass = 'badge bg-danger';
            } elseif ($svc->circuit_state_raw === 'halfopen') {
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
                echo html_writer::link(
                    $logsurl,
                    '<i class="fa fa-bar-chart"></i> ',
                    ['class' => 'btn btn-sm btn-outline-info me-1', 'title' => get_string('viewlogs', 'local_integrationhub')]
                );

                // Edit button.
                $editurl = new moodle_url($PAGE->url, ['action' => 'edit', 'serviceid' => $svc->id]);
                echo html_writer::link(
                    $editurl,
                    '<i class="fa fa-pencil"></i> ',
                    ['class' => 'btn btn-sm btn-outline-primary me-1', 'title' => get_string('editservice', 'local_integrationhub')]
                );

                // Delete button.
                $deleteurl = new moodle_url($PAGE->url, [
                    'action' => 'delete', 'serviceid' => $svc->id,
                    'confirm' => 1, 'sesskey' => sesskey(),
                ]);
                echo html_writer::link(
                    $deleteurl,
                    '<i class="fa fa-trash"></i> ',
                    ['class' => 'btn btn-sm btn-outline-danger me-1',
                     'title' => get_string('deleteservice', 'local_integrationhub'),
                     'onclick' => "return confirm('" .
                    addslashes_js(get_string('deleteconfirm', 'local_integrationhub', $svc->name)) . "');"]
                );

                // Reset circuit (only if not closed).
                if ($svc->circuit_state_raw !== 'closed') {
                    $reseturl = new moodle_url($PAGE->url, [
                        'action' => 'resetcircuit', 'serviceid' => $svc->id, 'sesskey' => sesskey(),
                    ]);
                    echo html_writer::link(
                        $reseturl,
                        '<i class="fa fa-refresh"></i> ',
                        ['class' => 'btn btn-sm btn-outline-warning',
                         'title' => get_string(
                             'resetcircuit',
                             'local_integrationhub'
                         )]
                    );
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
