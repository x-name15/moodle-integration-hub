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
 * CLI script to reset a circuit breaker for a service.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_integrationhub\service\registry;
use local_integrationhub\service\circuit_breaker;

// Options
[$options, $unrecognized] = cli_get_params([
    'service' => '',
    'help' => false,
], [
    's' => 'service',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['service'])) {
    $help = "
Resets the Circuit Breaker state for a given service.

Options:
-s, --service     Service name (slug) to reset.
-h, --help        Print out this help.

Example:
\$ php local/integrationhub/cli/reset_cb.php --service='python_test_service'
";
    cli_write($help);
    exit(0);
}

$servicename = $options['service'];

// Get service.
$service = registry::get_service($servicename);
if (!$service) {
    cli_error("Service '{$servicename}' not found.");
}

cli_heading("Resetting Circuit Breaker for '{$service->name}'");

// Reset via logic, not direct DB manipulation if possible, but DB is fine here for admin tool.
$DB->delete_records('local_integrationhub_cb', ['serviceid' => $service->id]);

// Re-init.
$cbrecord = new stdClass();
$cbrecord->serviceid = $service->id;
$cbrecord->state = 'closed';
$cbrecord->failure_count = 0;
$cbrecord->last_failure = null;
$cbrecord->timemodified = time();
$DB->insert_record('local_integrationhub_cb', $cbrecord);

echo "Successfully reset circuit breaker to CLOSED state.\n";
