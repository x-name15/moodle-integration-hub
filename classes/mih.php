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

namespace local_integrationhub;

defined('MOODLE_INTERNAL') || die();

use local_integrationhub\service\registry as service_registry;
use local_integrationhub\service\circuit_breaker;
use local_integrationhub\service\retry_policy;

/**
 * MIH — Main Integration Hub Gateway.
 *
 * Provides the public API for plugins to make HTTP requests to registered
 * external services. Handles authentication, circuit breaking, retries,
 * and logging transparently.
 *
 * Usage:
 *   $response = \local_integrationhub\mih::request('judgeman', '/execute', ['code' => $code]);
 *
 *   // Or Fluent:
 *   \local_integrationhub\mih::send('judgeman')->to('/execute')->with(['code' => $code])->dispatch();
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mih
{
    /** @var mih|null Singleton instance. */
    private static $instance = null;

    /** @var string Log table name. */
    const LOG_TABLE = 'local_integrationhub_log';

    /**
     * Get the singleton MIH instance.
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Static convenience method to make a request immediately.
     *
     * @param string $servicename Service slug.
     * @param string $endpoint    Endpoint path.
     * @param array  $payload     Request body.
     * @param string $method      HTTP method.
     * @return mih_response
     */
    public static function request(
        string $servicename,
        string $endpoint = '/',
        array $payload = [],
        string $method = 'POST'
        ): mih_response
    {
        return self::instance()->execute_request($servicename, $endpoint, $payload, $method);
    }

    /**
     * Start a fluent request chain.
     *
     * @param string $service Service slug.
     * @return mih_request
     */
    public static function send(string $service): mih_request
    {
        return new mih_request($service);
    }

    /**
     * Internal method to execute the request (formerly gateway->request).
     *
     * @param string $servicename The service slug (as registered in the dashboard).
     * @param string $endpoint    The endpoint path (e.g., '/execute') or AMQP routing key.
     * @param array  $payload     The request body data (will be JSON-encoded for POST/PUT).
     * @param string $method      HTTP method (GET/POST) or AMQP action.
     * @return gateway_response   The response wrapper.
     * @throws \moodle_exception  If the service is not found or circuit is open.
     */
    public function execute_request(
        string $servicename,
        string $endpoint = '/',
        array $payload = [],
        string $method = ''
        ): mih_response
    {

        // 1. Resolve the service from the registry.
        $service = service_registry::get_service($servicename);
        if (!$service) {
            throw new \moodle_exception('service_not_found', 'local_integrationhub', '', $servicename);
        }

        if (empty($service->enabled)) {
            throw new \moodle_exception('service_disabled', 'local_integrationhub', '', $servicename);
        }

        // 2. Check circuit breaker.
        $cb = circuit_breaker::from_service($service);
        if (!$cb->is_available()) {
            $this->log_request(
                $service->id,
                $endpoint,
                $method,
                null,
                0,
                0,
                false,
                'Circuit breaker is OPEN'
            );
            throw new \moodle_exception('circuit_open', 'local_integrationhub', '', $servicename);
        }

        // 3. Resolve Transport Driver.
        $type = $service->type ?? 'rest'; // Default to rest if not set.
        $transport = $this->get_transport_driver($type);

        // 4. Execute with retry policy.
        $retrypolicy = retry_policy::from_service($service);
        $httpstatus = null;
        $responsebody = null;
        $error = null;
        $success = false;
        $latencyms = 0;
        $attempts = 0;

        try {
            $result = $retrypolicy->execute(function ($attempt) use ($transport, $service, $endpoint, $payload, $method) {
                return $transport->execute($service, $endpoint, $payload, $method);
            });

            $success = $result['success'];
            $responsebody = $result['response'];
            $error = $result['error'];
            $latencyms = $result['latency'];
            $attempts = $result['attempts'];
            $httpstatus = $result['http_code'] ?? null;

            if ($success) {
                $cb->record_success();
            }
            else {
                $cb->record_failure();
            }
        }
        catch (\Exception $e) {
            $cb->record_failure();
            $error = $e->getMessage();
            $success = false;
        }

        // 5. Log the request.
        $this->log_request($service->id, $endpoint, $method, $httpstatus, $latencyms, $attempts, $success, $error);

        // 6. Return response wrapper.
        return new mih_response($success, $httpstatus, $responsebody, $error, $latencyms, $attempts);
    }

    /**
     * Factory method to get the correct transport driver.
     *
     * @param string $type The service type ('rest', 'amqp').
     * @return \local_integrationhub\transport\contract
     */
    private function get_transport_driver(string $type): \local_integrationhub\transport\contract
    {
        switch ($type) {
            case 'amqp':
                return new \local_integrationhub\transport\amqp();
            case 'soap':
                return new \local_integrationhub\transport\soap();
            case 'rest':
            default:
                return new \local_integrationhub\transport\http();
        }
    }

    /**
     * Log a request to the log table.
     *
     * @param int      $serviceid    Service ID.
     * @param string   $endpoint     Endpoint path.
     * @param string   $method       HTTP method.
     * @param int|null $httpstatus   Response HTTP status.
     * @param int      $latencyms   Latency in milliseconds.
     * @param int      $attempts    Number of attempts.
     * @param bool     $success     Whether the request was successful.
     * @param string|null $error    Error message if any.
     */
    private function log_request(
        int $serviceid,
        string $endpoint,
        string $method,
        ?int $httpstatus,
        int $latencyms,
        int $attempts,
        bool $success,
        ?string $error
        ): void
    {
        global $DB;

        $log = new \stdClass();
        $log->serviceid = $serviceid;
        $log->endpoint = $endpoint;
        $log->http_method = strtoupper($method);
        $log->http_status = $httpstatus;
        $log->latency_ms = $latencyms;
        $log->attempt_count = $attempts;
        $log->success = $success ? 1 : 0;
        $log->error_message = $error;
        $log->direction = 'outbound';
        $log->timecreated = time();

        try {
            $DB->insert_record(self::LOG_TABLE, $log);

            // Auto-purge old logs to prevent DB bloat.
            $maxlogs = (int)get_config('local_integrationhub', 'max_log_entries');
            if ($maxlogs <= 0) {
                $maxlogs = 500; // Fallback default.
            }
            $total = $DB->count_records(self::LOG_TABLE);
            if ($total > $maxlogs) {
                // Find the cutoff: get the timecreated of the Nth newest log.
                $cutoff = $DB->get_field_sql(
                    "SELECT timecreated FROM {" . self::LOG_TABLE . "}
                     ORDER BY timecreated DESC
                     LIMIT 1 OFFSET ?",
                [$maxlogs - 1]
                );
                if ($cutoff) {
                    $DB->delete_records_select(self::LOG_TABLE, 'timecreated < ?', [$cutoff]);
                }
            }
        }
        catch (\Exception $e) {
            // Don't let logging failures break the request flow.
            debugging('Integration Hub: Failed to log request: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Private constructor — use instance() instead.
     */
    private function __construct()
    {
    }
}