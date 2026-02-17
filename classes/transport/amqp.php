<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP Transport Driver.
 *
 * Handles publishing messages to RabbitMQ.
 * Expects 'base_url' to be a connection string (e.g. amqp://user:pass@host:5672).
 * Expects 'endpoint' to be the Routing Key or Queue name.
 */
class amqp implements contract {

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array {
        $starttime = microtime(true);
        $attempts = 1;

        try {
            $connection = amqp_helper::create_connection($service->base_url, (int)$service->timeout);
            $channel = $connection->channel();
            $routingkey = ltrim($endpoint, '/'); 
            amqp_helper::ensure_queue($channel, $routingkey);
            $msgbody = json_encode($payload);
            $msg = new AMQPMessage($msgbody, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json'
            ]);

            $exchange = ''; 
            
            $channel->basic_publish($msg, $exchange, $routingkey);

            // 3. Close.
            $channel->close();
            $connection->close();

            return [
                'success'   => true,
                'response'  => 'Published to ' . $routingkey,
                'error'     => null,
                'latency'   => (int)((microtime(true) - $starttime) * 1000),
                'attempts'  => $attempts
            ];

        } catch (\Exception $e) {
            return $this->error_result('AMQP Error: ' . $e->getMessage(), $starttime);
        }
    }

    private function error_result(string $msg, float $starttime): array {
        return [
            'success'   => false,
            'response'  => null,
            'error'     => $msg,
            'latency'   => (int)((microtime(true) - $starttime) * 1000),
            'attempts'  => 1
        ];
    }
}
