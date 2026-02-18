<?php
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
 */
class amqp implements contract
{
    use transport_utils;

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array
    {
        $starttime = microtime(true);
        $attempts = 1;

        try {
            // Parse configuration from URL query params
            $parsed_url = parse_url($service->base_url);
            $query = [];
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query);
            }

            // Connection Logic
            $connection = amqp_helper::create_connection($service->base_url, (int)$service->timeout);
            $channel = $connection->channel();

            // Determine Exchange and Routing Key
            $exchange = $query['exchange'] ?? '';

            // Routing Key: Rule/Endpoint overrides Config Default
            $routingkey = ltrim($endpoint, '/');
            if (empty($routingkey) && !empty($query['routing_key'])) {
                $routingkey = $query['routing_key'];
            }

            // Queue Declaration (Optional side-effect)
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
                'content_type' => 'application/json'
            ]);

            $channel->basic_publish($msg, $exchange, $routingkey);

            $channel->close();
            $connection->close();

            $target = empty($exchange) ? "DefEx -> RK:{$routingkey}" : "Ex:{$exchange} -> RK:{$routingkey}";
            return $this->success_result("Published to {$target}", $starttime, $attempts, 0);

        }
        catch (\Exception $e) {
            return $this->error_result('AMQP Error: ' . $e->getMessage(), $starttime, $attempts);
        }
    }
}