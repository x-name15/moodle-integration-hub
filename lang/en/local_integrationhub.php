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

$string['addrule'] = 'Add Rule';
$string['addservice'] = 'Add Service';
$string['allcircuitsreset'] = 'All service circuits have been reset.';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_dlq'] = 'Dead Letter Queue (Optional)';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_host'] = 'Host';
$string['amqp_pass'] = 'Password';
$string['amqp_port'] = 'Port';
$string['amqp_queue_declare'] = 'Queue to Declare (Optional)';
$string['amqp_queue_help'] = 'If set, this Queue will be declared (created) before publishing. Useful for "Work Queue" patterns.';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_routing_key_help'] = 'The default Routing Key used when publishing messages. Events can override this via the "Endpoint" field.';
$string['amqp_user'] = 'User';
$string['amqp_vhost'] = 'vHost';
$string['authtoken'] = 'Token / Credential';
$string['authtoken_help'] = 'The authentication token or API key for this service.';
$string['authtype'] = 'Authentication type: ';
$string['authtype_apikey'] = 'API Key';
$string['authtype_bearer'] = 'Bearer Token';
$string['avglatency'] = 'Avg Latency (ms)';
$string['base_url_help'] = 'Base URL of the external service.';
$string['baseurl'] = 'Base URL / Connection';
$string['cachedef_event_dedupe'] = 'Event deduplication cache';
$string['cachedef_log_counter'] = 'Log insertion counter for efficient purging';
$string['cachedef_rate_limit'] = 'Rate limiting cache for webhook protection';
$string['cancel'] = 'Cancel';
$string['cbcooldown'] = 'Circuit breaker cooldown (seconds)';
$string['cbcooldown_help'] = 'Seconds to wait before testing the service again (half-open state).';
$string['cbfailurethreshold'] = 'Circuit breaker threshold';
$string['cbfailurethreshold_help'] = 'Number of consecutive failures before the circuit breaker opens.';
$string['circuit_closed'] = 'CLOSED';
$string['circuit_halfopen'] = 'HALF-OPEN';
$string['circuit_open'] = 'OPEN';
$string['circuit_open_error'] = 'Circuit breaker for "{$a}" is OPEN. The service is unavailable.';
$string['circuitreset'] = 'Circuit breaker for "{$a}" has been reset.';
$string['clearlogs'] = 'Clear Logs';
$string['clearlogs_confirm'] = 'Are you sure you want to delete ALL logs? This cannot be undone.';
$string['close'] = 'Close';
$string['col_actions'] = 'Actions';
$string['col_attempts'] = 'Attempts';
$string['col_authtype'] = 'Auth';
$string['col_baseurl'] = 'Base URL';
$string['col_circuit'] = 'Circuit';
$string['col_created'] = 'Created';
$string['col_direction'] = 'Direction';
$string['col_enabled'] = 'Status';
$string['col_endpoint'] = 'Endpoint';
$string['col_error'] = 'Error';
$string['col_errors'] = 'Recent Errors';
$string['col_event'] = 'Event';
$string['col_failures'] = 'Failures';
$string['col_latency'] = 'Avg Latency';
$string['col_latency_ms'] = 'Latency (ms)';
$string['col_method'] = 'Method';
$string['col_name'] = 'Name';
$string['col_next_run'] = 'Next Run';
$string['col_service'] = 'Service';
$string['col_status'] = 'Status';
$string['col_success'] = 'Result';
$string['col_time'] = 'Time';
$string['col_type'] = 'Type';
$string['custom_headers'] = 'Custom Headers (JSON)';
$string['custom_headers_forbidden'] = 'Cannot override critical header: {$a}';
$string['custom_headers_help'] = 'Optional custom HTTP headers as JSON object. Example: {\"X-API-Version\": \"v2\", \"X-Client-ID\": \"moodle\"}. Note: Cannot override Authorization, Content-Type, or Accept headers.';
$string['custom_headers_invalid'] = 'Invalid JSON format for custom headers. Must be a valid JSON object.';
$string['dashboard'] = 'Dashboard';
$string['dashboard_desc'] = 'Manage your external service integrations from the Integration Hub dashboard.';
$string['default_max_retries'] = 'Default max retries';
$string['default_max_retries_desc'] = 'Default maximum retry attempts for new services.';
$string['default_timeout'] = 'Default timeout (seconds)';
$string['default_timeout_desc'] = 'Default HTTP request timeout for new services.';
$string['delete_dlq'] = 'Delete';
$string['deleteconfirm'] = 'Are you sure you want to delete the service "{$a}"? This will also delete all associated logs and circuit breaker state.';
$string['deleteconfirmrule'] = 'Are you sure you want to delete this rule?';
$string['deleteservice'] = 'Delete Service';
$string['direction'] = 'Direction';
$string['direction_inbound'] = 'Inbound';
$string['direction_outbound'] = 'Outbound';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_delete_confirm'] = 'Are you sure you want to delete this DLQ item?';
$string['dlq_deleted'] = 'Event deleted from the dead letter queue.';
$string['dlq_desc'] = 'Events that failed permanently and require manual intervention.';
$string['dlq_replayed'] = 'Event re-queued successfully.';
$string['editrule'] = 'Edit Rule';
$string['editservice'] = 'Edit Service';
$string['enabled'] = 'Enabled';
$string['error_invalid_url'] = 'Please enter a valid URL.';
$string['error_name_exists'] = 'A service with this name already exists.';
$string['error_name_required'] = 'Service name is required.';
$string['error_url_required'] = 'Base URL is required.';
$string['failed'] = 'Failed';
$string['failure'] = 'Failure';
$string['gotodashboard'] = 'Go to Dashboard';
$string['integrationhub:manage'] = 'Manage Integration Hub services';
$string['integrationhub:view'] = 'View Integration Hub dashboard';
$string['integrationstatus'] = 'Integration Status (All time)';
$string['latencytrend'] = 'Latency Trend (Last 200 requests)';
$string['latest_events_limit'] = 'Sent Events Limit';
$string['latest_events_limit_desc'] = 'Number of latest sent events to display in the "Sent Events" tab.';
$string['latest_events_title'] = 'Last {$a} Outbound Events';
$string['log_purge_check_frequency'] = 'Log purge check frequency';
$string['log_purge_check_frequency_desc'] = 'Check for log purging every N insertions. Higher values reduce overhead but may allow logs to grow temporarily beyond the limit. Recommended: 50-100.';
$string['logs'] = 'Request Logs';
$string['logs_cleared'] = 'All logs have been cleared.';
$string['logs_cleared_service'] = 'Logs for this service have been cleared.';
$string['max_log_entries'] = 'Max log entries';
$string['max_log_entries_desc'] = 'Maximum number of log entries to keep in the database. Older entries are automatically deleted when this limit is exceeded. Set to 0 for unlimited (not recommended).';
$string['maxretries'] = 'Max retries';
$string['no_dlq_items'] = 'No events in the dead letter queue.';
$string['no_events_logged'] = 'No outbound events logged recently.';
$string['no_pending_tasks'] = 'No pending tasks in the queue.';
$string['nologs'] = 'No requests logged yet.';
$string['norules'] = 'No integration rules defined yet.';
$string['noservices'] = 'No services registered yet. Click "Add Service" to get started.';
$string['orphans_purged'] = '{$a} orphan tasks purged successfully.';
$string['payload_final'] = 'Final Payload';
$string['payload_source'] = 'Source Data';
$string['pending'] = 'Pending';
$string['pluginname'] = 'Integration Hub';
$string['privacy:metadata'] = 'The Integration Hub plugin does not store personal user data. It only logs HTTP requests to external services.';
$string['privacy:metadata:external_services'] = 'Integration Hub dispatches Moodle event data to external services configured by the site administrator. The exact destination URLs are defined by the administrator and may vary. Event payloads may include personal data fields listed below.';
$string['privacy:metadata:external_services:courseid'] = 'The ID of the course associated with the event.';
$string['privacy:metadata:external_services:eventname'] = 'The name of the Moodle event that triggered the dispatch.';
$string['privacy:metadata:external_services:objectid'] = 'The ID of the object related to the event (e.g. course, assignment).';
$string['privacy:metadata:external_services:payload'] = 'The JSON payload sent to the external service, which may include any event context data.';
$string['privacy:metadata:external_services:userid'] = 'The ID of the user associated with the event.';
$string['purge_orphans'] = 'Purge Orphans';
$string['purge_orphans_confirm'] = 'Are you sure you want to delete all tasks whose rules have been deleted?';
$string['queue'] = 'Queue Monitor';
$string['queue_desc'] = 'Monitor pending and failed events waiting to be dispatched.';
$string['ratelimit_heading'] = 'Rate Limiting';
$string['ratelimit_heading_desc'] = 'Configure rate limits for incoming webhook requests to prevent abuse and DoS attacks.';
$string['refreshstatus'] = 'Refresh Status';
$string['replay'] = 'Replay';
$string['resetallcircuits'] = 'Reset All Circuits';
$string['resetcircuit'] = 'Reset Circuit';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'AMQP queue name to consume response messages from. Leave empty to disable inbound AMQP.';
$string['result_failure'] = 'FAIL';
$string['result_success'] = 'OK';
$string['retry'] = 'Retry';
$string['retry_now'] = 'Retry Now';
$string['retrybackoff'] = 'Retry backoff (seconds)';
$string['retrybackoff_help'] = 'Initial backoff delay for retries. Doubles with each attempt (exponential backoff).';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_event'] = 'Moodle Event';
$string['rule_event_help'] = 'Enter the full class name of the event (e.g. \core\event\user_created). You can select from the list or type a custom one. Note: Custom events must also be registered in db/events.php to be caught.';
$string['rule_method'] = 'HTTP Method';
$string['rule_service'] = 'Target Service';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Use placeholders like {{userid}}, {{courseid}}, {{objectid}}. Leave empty to send raw event data.';
$string['rulecreated'] = 'Rule created successfully.';
$string['ruledeleted'] = 'Rule deleted.';
$string['rules'] = 'Integration Rules';
$string['ruleupdated'] = 'Rule updated successfully.';
$string['saveservice'] = 'Save Service';
$string['selectevent'] = 'Select an event...';
$string['selectservice'] = 'Select a service...';
$string['sent_events'] = 'Sent Events';
$string['service_disabled'] = 'Service "{$a}" is currently disabled.';
$string['service_not_found'] = 'Service "{$a}" is not registered in Integration Hub.';
$string['servicecreated'] = 'Service "{$a}" created successfully.';
$string['servicedeleted'] = 'Service "{$a}" deleted successfully.';
$string['servicename'] = 'Identification Name / Slug';
$string['servicename_help'] = 'A unique identifier for this service. This name will be used in rules and code. Spaces are allowed but alphanumeric is recommended.';
$string['services'] = 'Registered Services';
$string['servicetype'] = 'Service Type';
$string['serviceupdated'] = 'Service "{$a}" updated successfully.';
$string['status_active'] = 'Active';
$string['status_disabled'] = 'Disabled';
$string['success'] = 'Success';
$string['task_consume_responses'] = 'Consume AMQP response messages';
$string['task_delete_confirm'] = 'Are you sure you want to delete this task?';
$string['task_delete_failed'] = 'Failed to delete task.';
$string['task_deleted'] = 'Task deleted successfully.';
$string['task_retried'] = 'Task prioritized for immediate execution.';
$string['task_retry_failed'] = 'Failed to retry task.';
$string['timeout'] = 'Timeout (seconds)';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_rest'] = 'REST API';
$string['type_soap'] = 'SOAP (Legacy)';
$string['url_help_amqp'] = 'Example: amqp://user:pass@host:5672/vhost';
$string['url_help_rest'] = 'Example: https://api.service.com/v1';
$string['view_payload'] = 'View Payload';
$string['viewlogs'] = 'View Logs';
$string['webhook_empty_body'] = 'Empty request body.';
$string['webhook_error'] = 'Error processing webhook.';
$string['webhook_invalid_json'] = 'Invalid JSON: {$a}';
$string['webhook_invalid_service'] = 'Service not found.';
$string['webhook_invalid_token'] = 'Invalid authentication token.';
$string['webhook_method_not_allowed'] = 'Method Not Allowed. Use POST.';
$string['webhook_missing_service'] = 'Missing required parameter: service.';
$string['webhook_rate_limit'] = 'Webhook rate limit (requests)';
$string['webhook_rate_limit_desc'] = 'Maximum number of requests allowed per IP address and service within the time window.';
$string['webhook_rate_limited'] = 'Rate limit exceeded. Too many requests.';
$string['webhook_rate_window'] = 'Webhook rate window (seconds)';
$string['webhook_rate_window_desc'] = 'Time window in seconds for rate limiting (e.g. 60 for per-minute limits).';
$string['webhook_received'] = 'Webhook received';
$string['webhook_service_disabled'] = 'Service is disabled.';
$string['webhook_success'] = 'Webhook processed successfully.';
