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

/**
 * Shared handler for inbound requests (HTTP webhooks & AMQP consumer).
 *
 * Logs the request, fires a Moodle event, and returns success/error.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook_handler
{
    /**
     * Process an inbound payload from an external service.
     *
     * @param \stdClass $service  The service record from local_integrationhub_svc.
     * @param array     $payload  The decoded JSON payload.
     * @param string    $source   The inbound source ('webhook' or 'amqp').
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function handle(\stdClass $service, array $payload, string $source = 'webhook'): array
    {
        global $DB;

        $starttime = microtime(true);

        try {
            // 1. Log the inbound request.
            $log = new \stdClass();
            $log->serviceid     = $service->id;
            $log->endpoint      = $source;
            $log->http_method   = 'INBOUND';
            $log->http_status   = null;
            $log->latency_ms    = 0;
            $log->attempt_count = 1;
            $log->success       = 1;
            $log->error_message = null;
            $log->direction     = 'inbound';
            $log->timecreated   = time();
            $DB->insert_record('local_integrationhub_log', $log);

            // 2. Fire Moodle event so other plugins can react.
            $event = \local_integrationhub\event\webhook_received::create([
                'context'  => \context_system::instance(),
                'other'    => [
                    'serviceid'   => (int)$service->id,
                    'servicename' => $service->name,
                    'source'      => $source,
                    'payload'     => $payload,
                ],
            ]);
            $event->trigger();

            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            // Log the error.
            $log = new \stdClass();
            $log->serviceid     = $service->id;
            $log->endpoint      = $source;
            $log->http_method   = 'INBOUND';
            $log->http_status   = null;
            $log->latency_ms    = (int)((microtime(true) - $starttime) * 1000);
            $log->attempt_count = 1;
            $log->success       = 0;
            $log->error_message = $e->getMessage();
            $log->direction     = 'inbound';
            $log->timecreated   = time();

            try {
                $DB->insert_record('local_integrationhub_log', $log);
            } catch (\Exception $logerror) {
                debugging('MIH: Failed to log inbound error: ' . $logerror->getMessage(), DEBUG_DEVELOPER);
            }

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
