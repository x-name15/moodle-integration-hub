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
 * Language strings for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Integration Hub';
$string['dashboard'] = 'Dashboard';
$string['dashboard_desc'] = 'Manage your external service integrations from the Integration Hub dashboard.';
$string['gotodashboard'] = 'Go to Dashboard';

// Capabilities.
$string['integrationhub:manage'] = 'Manage Integration Hub services';
$string['integrationhub:view'] = 'View Integration Hub dashboard';

// Settings.
$string['default_timeout'] = 'Default timeout (seconds)';
$string['default_timeout_desc'] = 'Default HTTP request timeout for new services.';
$string['default_max_retries'] = 'Default max retries';
$string['default_max_retries_desc'] = 'Default maximum retry attempts for new services.';
$string['max_log_entries'] = 'Max log entries';
$string['max_log_entries_desc'] = 'Maximum number of log entries to keep in the database. Older entries are automatically deleted when this limit is exceeded. Set to 0 for unlimited (not recommended).';

// Dashboard.
$string['services'] = 'Registered Services';
$string['noservices'] = 'No services registered yet. Click "Add Service" to get started.';
$string['addservice'] = 'Add Service';
$string['editservice'] = 'Edit Service';
$string['deleteservice'] = 'Delete Service';
$string['deleteconfirm'] = 'Are you sure you want to delete the service "{$a}"? This will also delete all associated logs and circuit breaker state.';
$string['viewlogs'] = 'View Logs';
$string['refreshstatus'] = 'Refresh Status';

// Service form.
$string['servicename'] = 'Identification Name / Slug';
$string['servicename_help'] = 'A unique identifier for this service. This name will be used in rules and code. Spaces are allowed but alphanumeric is recommended.';
$string['resetallcircuits'] = 'Reset All Circuits';
$string['allcircuitsreset'] = 'All service circuits have been reset.';
$string['resetcircuit'] = 'Reset Circuit';
$string['circuitreset'] = 'Circuit breaker for "{$a}" has been reset.';
$string['baseurl'] = 'Base URL / Connection';
$string['base_url_help'] = 'Base URL of the external service.';
$string['url_help_rest'] = 'Example: https://api.service.com/v1';
$string['url_help_amqp'] = 'Example: amqp://user:pass@host:5672/vhost';
$string['authtype'] = 'Authentication type: ';
$string['authtype_bearer'] = 'Bearer Token';
$string['authtype_apikey'] = 'API Key';
$string['authtoken'] = 'Token / Credential';
$string['authtoken_help'] = 'The authentication token or API key for this service.';
$string['timeout'] = 'Timeout (seconds)';
$string['maxretries'] = 'Max retries';
$string['retrybackoff'] = 'Retry backoff (seconds)';
$string['retrybackoff_help'] = 'Initial backoff delay for retries. Doubles with each attempt (exponential backoff).';
$string['cbfailurethreshold'] = 'Circuit breaker threshold';
$string['cbfailurethreshold_help'] = 'Number of consecutive failures before the circuit breaker opens.';
$string['cbcooldown'] = 'Circuit breaker cooldown (seconds)';
$string['cbcooldown_help'] = 'Seconds to wait before testing the service again (half-open state).';
$string['enabled'] = 'Enabled';

// Service form actions.
$string['saveservice'] = 'Save Service';
$string['cancel'] = 'Cancel';
$string['servicecreated'] = 'Service "{$a}" created successfully.';
$string['serviceupdated'] = 'Service "{$a}" updated successfully.';
$string['servicedeleted'] = 'Service "{$a}" deleted successfully.';

// Table headers.
$string['col_name'] = 'Name';
$string['col_baseurl'] = 'Base URL';
$string['col_authtype'] = 'Auth';
$string['col_circuit'] = 'Circuit';
$string['col_latency'] = 'Avg Latency';
$string['col_errors'] = 'Recent Errors';
$string['col_actions'] = 'Actions';
$string['col_enabled'] = 'Status';
$string['status_active'] = 'Active';
$string['status_disabled'] = 'Disabled';

// Circuit states.
$string['circuit_closed'] = 'CLOSED';
$string['circuit_open'] = 'OPEN';
$string['circuit_halfopen'] = 'HALF-OPEN';

// Errors.
$string['service_not_found'] = 'Service "{$a}" is not registered in Integration Hub.';
$string['service_disabled'] = 'Service "{$a}" is currently disabled.';
$string['circuit_open_error'] = 'Circuit breaker for "{$a}" is OPEN. The service is unavailable.';
$string['error_name_exists'] = 'A service with this name already exists.';
$string['error_invalid_url'] = 'Please enter a valid URL.';
$string['error_name_required'] = 'Service name is required.';
$string['error_url_required'] = 'Base URL is required.';

// Logs.
$string['logs'] = 'Request Logs';
$string['nologs'] = 'No requests logged yet.';
$string['col_endpoint'] = 'Endpoint';
$string['col_method'] = 'Method';
$string['col_status'] = 'Status';
$string['col_latency_ms'] = 'Latency (ms)';
$string['col_attempts'] = 'Attempts';
$string['col_success'] = 'Result';
$string['col_error'] = 'Error';
$string['col_time'] = 'Time';
$string['result_success'] = 'OK';
$string['result_failure'] = 'FAIL';
$string['clearlogs'] = 'Clear Logs';
$string['clearlogs_confirm'] = 'Are you sure you want to delete ALL logs? This cannot be undone.';
$string['logs_cleared'] = 'All logs have been cleared.';
$string['logs_cleared_service'] = 'Logs for this service have been cleared.';

// Privacy.
$string['privacy:metadata'] = 'The Integration Hub plugin does not store personal user data. It only logs HTTP requests to external services.';

// Event Rules.
$string['rules'] = 'Integration Rules';
$string['addrule'] = 'Add Rule';
$string['editrule'] = 'Edit Rule';
$string['rule_event'] = 'Moodle Event';
$string['rule_event_help'] = 'Enter the full class name of the event (e.g. \core\event\user_created). You can select from the list or type a custom one. Note: Custom events must also be registered in db/events.php to be caught.';
$string['rule_service'] = 'Target Service';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_method'] = 'HTTP Method';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Use placeholders like {{userid}}, {{courseid}}, {{objectid}}. Leave empty to send raw event data.';
$string['selectevent'] = 'Select an event...';
$string['selectservice'] = 'Select a service...';
$string['rulecreated'] = 'Rule created successfully.';
$string['ruleupdated'] = 'Rule updated successfully.';
$string['ruledeleted'] = 'Rule deleted.';
$string['deleteconfirmrule'] = 'Are you sure you want to delete this rule?';
$string['col_type'] = 'Type';
$string['col_event'] = 'Event';
$string['col_service'] = 'Service';
$string['norules'] = 'No integration rules defined yet.';
$string['servicetype'] = 'Service Type';
$string['type_rest'] = 'REST API';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_soap'] = 'SOAP (Legacy)';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_host'] = 'Host';
$string['amqp_port'] = 'Port';
$string['amqp_user'] = 'User';
$string['amqp_pass'] = 'Password';
$string['amqp_vhost'] = 'vHost';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_queue_declare'] = 'Queue to Declare (Optional)';
$string['amqp_routing_key_help'] = 'The default Routing Key used when publishing messages. Events can override this via the "Endpoint" field.';
$string['amqp_queue_help'] = 'If set, this Queue will be declared (created) before publishing. Useful for "Work Queue" patterns.';
$string['amqp_dlq'] = 'Dead Letter Queue (Optional)';

// Queue.
$string['queue'] = 'Queue Monitor';
$string['queue_desc'] = 'Monitor pending and failed events waiting to be dispatched.';
$string['no_pending_tasks'] = 'No pending tasks in the queue.';
$string['col_failures'] = 'Failures';
$string['col_next_run'] = 'Next Run';
$string['col_created'] = 'Created';
$string['retry_now'] = 'Retry Now';
$string['failed'] = 'Failed';
$string['retry'] = 'Retry';
$string['pending'] = 'Pending';
$string['task_retried'] = 'Task prioritized for immediate execution.';
$string['task_retry_failed'] = 'Failed to retry task.';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_desc'] = 'Events that failed permanently and require manual intervention.';
$string['replay'] = 'Replay';
$string['delete_dlq'] = 'Delete';
$string['no_dlq_items'] = 'No events in the dead letter queue.';
$string['dlq_replayed'] = 'Event re-queued successfully.';
$string['dlq_deleted'] = 'Event deleted from the dead letter queue.';
$string['task_deleted'] = 'Task deleted successfully.';
$string['task_delete_failed'] = 'Failed to delete task.';
$string['orphans_purged'] = '{$a} orphan tasks purged successfully.';
$string['purge_orphans'] = 'Purge Orphans';
$string['purge_orphans_confirm'] = 'Are you sure you want to delete all tasks whose rules have been deleted?';
$string['task_delete_confirm'] = 'Are you sure you want to delete this task?';
$string['dlq_delete_confirm'] = 'Are you sure you want to delete this DLQ item?';

// Queue Payload Viewer
$string['view_payload'] = 'View Payload';
$string['payload_source'] = 'Source Data';
$string['payload_final'] = 'Final Payload';
$string['close'] = 'Close';

// Dashboard Charts.
$string['integrationstatus'] = 'Integration Status (All time)';
$string['latencytrend'] = 'Latency Trend (Last 200 requests)';
$string['success'] = 'Success';
$string['failure'] = 'Failure';
$string['avglatency'] = 'Avg Latency (ms)';

// Webhook & Bidirectional.
$string['webhook_received'] = 'Webhook received';
$string['webhook_invalid_token'] = 'Invalid authentication token.';
$string['webhook_invalid_service'] = 'Service not found.';
$string['webhook_success'] = 'Webhook processed successfully.';
$string['webhook_error'] = 'Error processing webhook.';
$string['direction'] = 'Direction';
$string['direction_outbound'] = 'Outbound';
$string['direction_inbound'] = 'Inbound';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'AMQP queue name to consume response messages from. Leave empty to disable inbound AMQP.';
$string['task_consume_responses'] = 'Consume AMQP response messages';
$string['col_direction'] = 'Direction';

// Sent Events Page.
$string['sent_events'] = 'Sent Events';
$string['latest_events_title'] = 'Last {$a} Outbound Events';
$string['latest_events_limit'] = 'Sent Events Limit';
$string['latest_events_limit_desc'] = 'Number of latest sent events to display in the "Sent Events" tab.';
$string['no_events_logged'] = 'No outbound events logged recently.';