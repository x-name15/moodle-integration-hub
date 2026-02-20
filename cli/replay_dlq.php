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
 * CLI script to replay items from the Dead Letter Queue.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Options
[$options, $unrecognized] = cli_get_params([
    'id' => 0,
    'all' => false,
    'help' => false,
], [
    'i' => 'id',
    'a' => 'all',
    'h' => 'help',
]);

if ($options['help'] || (empty($options['id']) && empty($options['all']))) {
    $help = "
Replays failed events from the Dead Letter Queue (DLQ).

Options:
-i, --id          DLQ Entry ID to replay.
-a, --all         Replay ALL items in the DLQ.
-h, --help        Print out this help.

Example:
\$ php local/integrationhub/cli/replay_dlq.php --id=5
\$ php local/integrationhub/cli/replay_dlq.php --all
";
    cli_write($help);
    exit(0);
}

cli_heading("DLQ Replay Tool");

$toreplay = [];

if ($options['all']) {
    $toreplay = $DB->get_records('local_integrationhub_dlq');
    echo "Found " . count($toreplay) . " items in DLQ.\n";
} else {
    $record = $DB->get_record('local_integrationhub_dlq', ['id' => $options['id']]);
    if ($record) {
        $toreplay[] = $record;
    } else {
        cli_error("DLQ Entry {$options['id']} not found.");
    }
}

foreach ($toreplay as $item) {
    echo "Replaying Item {$item->id} (Event: {$item->eventname})...\n";

    // Logic: Create a new adhoc task with the saved payload.
    // NOTE: We might need to reconstruct the original 'eventdata' if the payload was already templated.
    // If DLQ stores the *final* payload, we can't easily re-run the template logic if the source data changed.
    // But typically we want to retry sending the *same* payload.
    // However, dispatch_event_task expects raw 'eventdata' to re-apply templates.
    // If DLQ saved the *raw* eventdata, we are good. Let's assume DLQ saves something useful.

    // Checking dispatch_event_task:
    // $dlq->payload = json_encode($payload); -> This is the FINAL payload (after template).
    // The task expects 'eventdata' to run template logic again.
    // This is a design limitation. We should probably just send the payload directly via Gateway if we are replaying.
    // But we need the Service ID and Endpoint info.

    $rule = $DB->get_record('local_integrationhub_rules', ['serviceid' => $item->serviceid, 'eventname' => $item->eventname], '*', IGNORE_MULTIPLE);
    if (!$rule) {
        echo "  [SKIP] No matching rule found for service {$item->serviceid}.\n";
        continue;
    }

    // We can't easily use the existing task logic because it expects raw event data.
    // We will cheat and queue a "Replay Task" or just execute directly here since it's CLI.
    // Let's execute directly via Gateway to confirm it works.

    try {
        // Use MIH Facade
        $service = \local_integrationhub\service\registry::get_service_by_id($item->serviceid);
        if (!$service) {
            echo "  [SKIP] Service {$item->serviceid} not found.\n";
            continue;
        }

        $payload = json_decode($item->payload, true);
        $endpoint = !empty($rule->endpoint) ? $rule->endpoint : '/';
        $method = ($service->type === 'amqp') ? 'AMQP' : 'POST';

        $response = \local_integrationhub\mih::instance()->execute_request($service->name, $endpoint, $payload, $method);

        if ($response->is_ok()) {
            echo "  [SUCCESS] Replayed successfully.\n";
            $DB->delete_records('local_integrationhub_dlq', ['id' => $item->id]);
        } else {
            echo "  [FAIL] MIH error: {$response->error}\n";
        }
    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
