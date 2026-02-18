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

namespace local_integrationhub\task;

defined('MOODLE_INTERNAL') || die();

use local_integrationhub\mih;
use local_integrationhub\service\registry as service_registry;

/**
 * Adhoc task to dispatch Moodle events to external services.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dispatch_event_task extends \core\task\adhoc_task
{

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;

        $data = $this->get_custom_data();
        $ruleid = $data->ruleid;
        $eventdata = (array)$data->eventdata; // Ensure array for gateway.

        // Load rule.
        $rule = $DB->get_record('local_integrationhub_rules', ['id' => $ruleid]);
        if (!$rule || !$rule->enabled) {
            mtrace("Rule {$ruleid} not found or disabled. Skipping.");
            return;
        }

        // Load service.
        $service = service_registry::get_service_by_id($rule->serviceid);
        if (!$service || !$service->enabled) {
            mtrace("Service {$rule->serviceid} not found or disabled. Skipping.");
            return;
        }

        // Prepare payload.
        // We replace {{key}} with values from $eventdata (which is flattened).
        $template = $rule->payload_template;
        if (empty($template)) {
            // Default payload is raw event data if no template provided.
            $payload = $eventdata;
        }
        else {
            $json = $template;
            // Simple string replacement for now. Flatten event data for easy access.
            // e.g. {{objectid}}, {{userid}}, {{courseid}}.
            foreach ($eventdata as $key => $value) {
                if (is_scalar($value)) {
                    $replacement = $value;
                    if (is_string($value)) {
                        // Escape for JSON string context (removes surrounding quotes from json_encode).
                        $replacement = substr(json_encode($value), 1, -1);
                    }
                    elseif (is_bool($value)) {
                        $replacement = $value ? 'true' : 'false';
                    }
                    $json = str_replace('{{' . $key . '}}', $replacement, $json);
                }
            }
            $payload = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                mtrace("Invalid JSON template for rule {$ruleid}: " . json_last_error_msg());
                // Fallback to raw data? Or fail? Let's log error and fail this task attempt.
                throw new \moodle_exception('Invalid JSON template in rule ' . $ruleid);
            }
        }

        // Determine request method.
        if (isset($service->type) && $service->type === 'amqp') {
            $method = 'AMQP';
        }
        elseif (!empty($rule->http_method)) {
            $method = $rule->http_method;
        }
        else {
            $method = 'POST';
        }

        // Endpoint override?
        $endpoint = !empty($rule->endpoint) ? $rule->endpoint : '/';

        mtrace("Dispatching event '{$rule->eventname}' to service '{$service->name}' at endpoint '{$endpoint}'...");

        try {
            $mih = mih::instance();

            // Log payload for debugging.
            mtrace("Payload: " . json_encode($payload));

            $response = $mih->execute_request($service->name, $endpoint, $payload, $method);

            if ($response->is_ok()) {
                $status_str = $response->httpstatus ? "HTTP {$response->httpstatus}" : "OK";
                mtrace("Success: {$status_str}");
            }
            else {
                mtrace("Failed: HTTP {$response->httpstatus} - {$response->error}");
                mtrace("Response Body: " . $response->body);
                // If the gateway fails after its own internal retries, we throw to let Moodle retry the task.
                throw new \moodle_exception('gateway_error', 'local_integrationhub', '', $response->error);
            }
        }
        catch (\Exception $e) {
            mtrace("Exception: " . $e->getMessage());

            // Track attempts.
            $attempts = (isset($data->attempts) ? $data->attempts : 0) + 1;
            $data->attempts = $attempts;
            $this->set_custom_data($data);

            // Update the task record in DB so custom_data is persisted for next Moodle retry.
            $DB->set_field('task_adhoc', 'customdata', json_encode($data), ['id' => $this->get_id()]);

            if ($attempts >= 5) {
                mtrace("Reached max attempts (5). Moving to DLQ.");
                $this->move_to_dlq($rule, $payload ?? $eventdata, $e->getMessage());
                return; // Stop Moodle from retrying further.
            }
            throw $e; // Rethrow for Moodle's native retry mechanism.
        }
    }

    /**
     * Move a failed event to the Dead Letter Queue.
     *
     * @param \stdClass $rule     The rule object.
     * @param array     $payload  The payload (templated or raw).
     * @param string    $error    The error message.
     */
    protected function move_to_dlq($rule, $payload, $error)
    {
        global $DB;
        $dlq = new \stdClass();
        $dlq->eventname = $rule->eventname;
        $dlq->serviceid = $rule->serviceid;
        $dlq->payload = json_encode($payload);
        $dlq->error_message = $error;
        $dlq->timecreated = time();
        $DB->insert_record('local_integrationhub_dlq', $dlq);
    }
}