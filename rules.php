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

require_once(__DIR__ . '/../../config.php');

use local_integrationhub\rule\registry as rules_registry;
use local_integrationhub\service\registry as service_registry;

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:view', $context);
$canmanage = has_capability('local/integrationhub:manage', $context);

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$ruleid = optional_param('ruleid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/integrationhub/rules.php'));
$PAGE->set_title(get_string('pluginname', 'local_integrationhub') . ' - Rules');
$PAGE->set_heading(get_string('pluginname', 'local_integrationhub'));
$PAGE->set_pagelayout('admin');

// Process save.
if ($canmanage && $action === 'save' && confirm_sesskey()) {
    $data = new stdClass();
    $data->eventname        = required_param('eventname', PARAM_RAW); // Allow backslashes.
    $data->serviceid        = required_param('serviceid', PARAM_INT);
    $data->endpoint         = optional_param('endpoint', '', PARAM_RAW);
    $data->payload_template = optional_param('payload_template', '', PARAM_RAW);
    $data->enabled          = optional_param('enabled', 0, PARAM_INT);

    if ($ruleid > 0) {
        rules_registry::update_rule($ruleid, $data);
        \core\notification::success(get_string('ruleupdated', 'local_integrationhub'));
    } else {
        rules_registry::create_rule($data);
        \core\notification::success(get_string('rulecreated', 'local_integrationhub'));
    }
    redirect($PAGE->url);
}

// Process delete.
if ($canmanage && $action === 'delete' && $ruleid > 0 && confirm_sesskey()) {
    if ($confirm) {
        rules_registry::delete_rule($ruleid);
        \core\notification::success(get_string('ruledeleted', 'local_integrationhub'));
        redirect($PAGE->url);
    }
}

// Load data.
$rules = rules_registry::get_all_rules();
$services = service_registry::get_all_services();
$allevents = rules_registry::get_all_events_dynamic();
$commonevents = rules_registry::get_common_events();
$editrule = null;

if ($canmanage && $action === 'edit' && $ruleid > 0) {
    $editrule = rules_registry::get_rule($ruleid);
}

echo $OUTPUT->header();

// Tabs navigation.
echo html_writer::start_div('mb-4');
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs']);
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/index.php'), get_string('services', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/rules.php'), get_string('rules', 'local_integrationhub'), ['class' => 'nav-link active']);
echo html_writer::end_tag('li');
echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link(new moodle_url('/local/integrationhub/queue.php'), get_string('queue', 'local_integrationhub'), ['class' => 'nav-link']);
echo html_writer::end_tag('li');
echo html_writer::end_tag('ul');
echo html_writer::end_div();

// Action buttons.
if ($canmanage) {
    echo html_writer::start_div('mb-4 d-flex', ['style' => 'gap: 12px;']);
    echo html_writer::tag('button', get_string('addrule', 'local_integrationhub'), [
        'class' => 'btn btn-primary',
        'id'    => 'ih-btn-add',
        'type'  => 'button',
    ]);
    echo html_writer::end_div();
}

// Form.
$showform = ($canmanage && ($action === 'add' || $editrule));
$formclass = $showform ? '' : 'd-none';

echo html_writer::start_div("card mb-4 {$formclass}", ['id' => 'ih-rule-form']);
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', $editrule ? get_string('editrule', 'local_integrationhub') : get_string('addrule', 'local_integrationhub'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false), 'id' => 'ih-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'ruleid', 'value' => $editrule ? $editrule->id : 0, 'id' => 'ih-ruleid']);

echo '<div class="row">';

// Get all events from system.
$allevents = rules_registry::get_all_events_dynamic();

// Event selector (Datalist to allow custom events).
echo '<div class="col-md-6 mb-3">';
echo html_writer::tag('label', get_string('rule_event', 'local_integrationhub'), ['for' => 'ih-eventname', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 
    'name' => 'eventname', 
    'id' => 'ih-eventname', 
    'class' => 'form-control', 
    'list' => 'ih-eventlist', 
    'required' => 'required',
    'placeholder' => '\core\event\user_created',
    'value' => $editrule->eventname ?? ''
]);
echo html_writer::start_tag('datalist', ['id' => 'ih-eventlist']);
foreach ($allevents as $classname => $label) {
    echo "<option value='{$classname}'>" . s($label) . "</option>";
}
echo html_writer::end_tag('datalist');
echo html_writer::tag('div', get_string('rule_event_help', 'local_integrationhub'), ['class' => 'form-text text-muted']);
echo '</div>';

// Service selector.
echo '<div class="col-md-6 mb-3">';
echo html_writer::tag('label', get_string('rule_service', 'local_integrationhub'), ['for' => 'ih-serviceid', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']);
echo html_writer::start_tag('select', ['name' => 'serviceid', 'id' => 'ih-serviceid', 'class' => 'form-select', 'required' => 'required']);
echo '<option value="">' . get_string('selectservice', 'local_integrationhub') . '</option>';
$currentsvc = $editrule->serviceid ?? 0;
foreach ($services as $svc) {
    if (!$svc->enabled) continue;
    $sel = ($svc->id == $currentsvc) ? 'selected' : '';
    echo "<option value='{$svc->id}' {$sel}>" . s($svc->name) . "</option>";
}
echo html_writer::end_tag('select');
echo '</div>';

// Endpoint override.
echo '<div class="col-md-6 mb-3">';
echo html_writer::tag('label', get_string('rule_endpoint', 'local_integrationhub'), ['for' => 'ih-endpoint', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'endpoint', 'id' => 'ih-endpoint', 'class' => 'form-control',
    'value' => $editrule->endpoint ?? '', 'placeholder' => '/override/path (optional)'
]);
echo '</div>';

// Enabled.
echo '<div class="col-md-6 mb-3 d-flex align-items-center">';
echo html_writer::start_div('form-check mt-4');
$checked = (!isset($editrule) || $editrule->enabled) ? 'checked' : '';
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 
    'name' => 'enabled', 
    'value' => 1, 
    'checked' => $checked ? 'checked' : null,
    'class' => 'form-check-input', 
    'id' => 'ih-enabled'
]);
echo html_writer::tag('label', get_string('enabled', 'local_integrationhub'), ['for' => 'ih-enabled', 'class' => 'form-check-label']);
echo html_writer::end_div();
echo '</div>';

// Payload template.
echo '<div class="col-12 mb-3">';
echo html_writer::tag('label', get_string('rule_template', 'local_integrationhub'), ['for' => 'ih-template', 'class' => 'form-label', 'style' => 'display:block; margin-bottom:6px;']);
echo html_writer::tag('textarea', $editrule->payload_template ?? '{"event": "{{eventname}}", "user": "{{userid}}"}', [
    'name' => 'payload_template', 'id' => 'ih-template', 'class' => 'form-control', 'rows' => 4,
    'style' => 'font-family: monospace;'
]);
echo html_writer::start_div('mt-2 d-flex justify-content-between align-items-center');
echo html_writer::tag('div', get_string('rule_template_help', 'local_integrationhub'), ['class' => 'form-text text-muted']);
echo html_writer::tag('button', 'Preview Payload', [
    'class' => 'btn btn-sm btn-outline-info',
    'id'    => 'ih-btn-preview',
    'type'  => 'button'
]);
echo html_writer::end_div();
echo '</div>';

echo '</div>'; // .row

// Buttons.
echo html_writer::start_div('d-flex', ['style' => 'gap: 12px;']);
echo html_writer::tag('button', get_string('save', 'core'), ['type' => 'submit', 'class' => 'btn btn-success']);
echo html_writer::tag('button', get_string('cancel', 'core'), ['type' => 'button', 'class' => 'btn btn-secondary', 'id' => 'ih-btn-cancel']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div(); // .card-body
echo html_writer::end_div(); // .card

// CSS Fix for Moodle Themes (Force dark text on hover).
echo html_writer::tag('style', "
    .table-hover tbody tr:hover { color: #212529 !important; }
    .table-striped tbody tr:nth-of-type(odd) { color: #212529 !important; }
");

// Table.
echo html_writer::tag('h4', get_string('rules', 'local_integrationhub'), ['class' => 'mb-3']);

if (empty($rules)) {
    echo html_writer::div(get_string('norules', 'local_integrationhub'), 'alert alert-info');
} else {
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    // Force text-dark to avoid theme white-text issues
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'style' => 'color: #212529 !important;']);
    echo '<thead class="table-dark"><tr>';
    echo '<th>' . get_string('col_event', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_service', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_endpoint', 'local_integrationhub') . '</th>';
    echo '<th>' . get_string('col_enabled', 'local_integrationhub') . '</th>';
    if ($canmanage) echo '<th>' . get_string('col_actions', 'local_integrationhub') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($rules as $rule) {
        echo '<tr>';
        $displayname = rules_registry::get_event_display_name($rule->eventname);
        echo '<td>' . s($displayname) . '<br><small class="text-muted">' . s($rule->eventname) . '</small></td>';
        
        $svcname = $services[$rule->serviceid]->name ?? 'Unknown ID:' . $rule->serviceid;
        echo '<td>' . s($svcname) . '</td>';
        
        echo '<td>' . ($rule->endpoint ? html_writer::tag('code', s($rule->endpoint)) : '<span class="text-muted">Default</span>') . '</td>';
        
        $statusclass = $rule->enabled ? 'badge bg-success' : 'badge bg-secondary';
        $statuslabel = $rule->enabled ? get_string('status_active', 'local_integrationhub') : get_string('status_disabled', 'local_integrationhub');
        echo '<td><span class="' . $statusclass . '">' . $statuslabel . '</span></td>';
        
        if ($canmanage) {
            echo '<td>';
            $editurl = new moodle_url($PAGE->url, ['action' => 'edit', 'ruleid' => $rule->id]);
            echo html_writer::link($editurl, '<i class="fa fa-pencil"></i>', ['class' => 'btn btn-sm btn-outline-primary me-1']);
            
            $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'ruleid' => $rule->id, 'confirm' => 1, 'sesskey' => sesskey()]);
            echo html_writer::link($deleteurl, '<i class="fa fa-trash"></i>', [
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => "return confirm('" . addslashes_js(get_string('deleteconfirmrule', 'local_integrationhub')) . "');"
            ]);
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

// Initialize AMD module.
$servicetypes = [];
foreach ($services as $s) {
    $servicetypes[$s->id] = $s->type ?? 'rest';
}
$PAGE->requires->js_call_amd('local_integrationhub/rules', 'init', [$servicetypes]);

echo $OUTPUT->footer();
