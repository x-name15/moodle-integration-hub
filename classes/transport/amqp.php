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

namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP Transport Driver.
 *
 * Handles publishing messages to RabbitMQ.
 * Expects 'base_url' to be a connection string (e.g. amqp://user:pass@host:5672).
 * Expects 'endpoint' to be the Routing Key or Queue name.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amqp implements contract
{
    use transport_utils;

    /**
     * Execute a request using the AMQP transport.
     *
     * @param \stdClass $service The service definition object.
     * @param string $endpoint   The Routing Key or Queue name.
     * @param array $payload     The data to send.
     * @param string $method   Not used for AMQP, kept for interface compliance.
     * @return array Response payload
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array {
        $starttime = microtime(true);
        $attempts = 1;

        try {
            // Parse configuration from URL query params.
            $parsedurl = parse_url($service->base_url);
            $query = [];
            if (isset($parsedurl['query'])) {
                parse_str($parsedurl['query'], $query);
            }

            // Connection Logic.
            $connection = amqp_helper::create_connection($service->base_url, (int)$service->timeout);
            $channel = $connection->channel();

            // Determine Exchange and Routing Key.
            $exchange = $query['exchange'] ?? '';

            // Routing Key: Rule/Endpoint overrides Config Default.
            $routingkey = ltrim($endpoint, '/');
            if (empty($routingkey) && !empty($query['routing_key'])) {
                $routingkey = $query['routing_key'];
            }

            // Queue Declaration (Optional side-effect).
            // Only declare if 'queue_declare' param IS SET.
            if (!empty($query['queue_declare'])) {
                amqp_helper::ensure_queue($channel, $query['queue_declare']);
            }

            // Fallback: If no Exchange and no Routing Key are specified, but we declared a queue,
            // assume we want to publish to that queue (Direct Queue Pattern).
            if (empty($exchange) && empty($routingkey) && !empty($query['queue_declare'])) {
                $routingkey = $query['queue_declare'];
            }

            // Implicit "Direct to Queue" fallback:
            // If Exchange is empty AND we have a Routing Key, RabbitMQ treats it as "Send to Queue named X".
            // In this specific case, if the user didn't ask to declare explicitly,
            // should we do it anyway to ensure delivery?
            // The user wants control. If they didn't put it in "Queue to Declare", we don't declare.
            // BUT: Old behavior was "ensure_queue($routingkey)".
            // Let's Respect the new field strictly: Only declare if 'queue_declare' is present.

            $msgbody = json_encode($payload);
            $msg = new AMQPMessage($msgbody, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
            ]);

            $channel->basic_publish($msg, $exchange, $routingkey);

            $channel->close();
            $connection->close();

            $target = empty($exchange) ? "DefEx -> RK:{$routingkey}" : "Ex:{$exchange} -> RK:{$routingkey}";
            return $this->success_result("Published to {$target}", $starttime, $attempts, 0);
        } catch (\Exception $e) {
            return $this->error_result('AMQP Error: ' . $e->getMessage(), $starttime, $attempts);
        }
    }
}
