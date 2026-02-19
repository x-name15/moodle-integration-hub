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
 * Admin settings for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_integrationhub',
        get_string('pluginname', 'local_integrationhub')
        );

    // Link to the dashboard.
    $settings->add(new admin_setting_heading(
        'local_integrationhub/dashboardlink',
        get_string('dashboard', 'local_integrationhub'),
        get_string('dashboard_desc', 'local_integrationhub') . ' ' .
        html_writer::link(
        new moodle_url('/local/integrationhub/index.php'),
        get_string('gotodashboard', 'local_integrationhub')
    )
        ));

    // Firewall master switch.
    $settings->add(new admin_setting_configcheckbox(
        'local_integrationhub/enable_firewall',
        get_string('enable_firewall', 'local_integrationhub'),
        get_string('enable_firewall_desc', 'local_integrationhub'),
        1
    ));

    // Default timeout for new services.
    $settings->add(new admin_setting_configtext(
        'local_integrationhub/default_timeout',
        get_string('default_timeout', 'local_integrationhub'),
        get_string('default_timeout_desc', 'local_integrationhub'),
        5,
        PARAM_INT
        ));

    // Default max retries for new services.
    $settings->add(new admin_setting_configtext(
        'local_integrationhub/default_max_retries',
        get_string('default_max_retries', 'local_integrationhub'),
        get_string('default_max_retries_desc', 'local_integrationhub'),
        3,
        PARAM_INT
        ));

    // Max log entries (auto-purge old logs).
    $settings->add(new admin_setting_configtext(
        'local_integrationhub/max_log_entries',
        get_string('max_log_entries', 'local_integrationhub'),
        get_string('max_log_entries_desc', 'local_integrationhub'),
        500,
        PARAM_INT
        ));

    // Limit for 'Sent Events' tab.
    $settings->add(new admin_setting_configtext(
        'local_integrationhub/latest_events_limit',
        get_string('latest_events_limit', 'local_integrationhub'),
        get_string('latest_events_limit_desc', 'local_integrationhub'),
        50,
        PARAM_INT
        ));

    $ADMIN->add('localplugins', $settings);
}