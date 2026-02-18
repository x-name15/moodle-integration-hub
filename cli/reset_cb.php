<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_integrationhub\service\registry;
use local_integrationhub\service\circuit_breaker;

// Options
list($options, $unrecognized) = cli_get_params([
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
$cb_record = new stdClass();
$cb_record->serviceid = $service->id;
$cb_record->state = 'closed';
$cb_record->failure_count = 0;
$cb_record->last_failure = null;
$cb_record->timemodified = time();
$DB->insert_record('local_integrationhub_cb', $cb_record);

echo "Successfully reset circuit breaker to CLOSED state.\n";