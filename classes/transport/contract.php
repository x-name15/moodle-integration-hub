<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface contract.
 *
 * Defines the method signature for all transport drivers (HTTP, AMQP, etc).
 */
interface contract {
    /**
     * Execute a request using this transport.
     *
     * @param \stdClass $service The service definition object.
     * @param string $endpoint   The target endpoint (or queue/exchange).
     * @param array $payload     The data to send.
     * @param string $method     (Optional) Action method (e.g. GET/POST for HTTP, Routing Key for AMQP).
     * @return array             ['success' => bool, 'response' => mixed, 'error' => string|null, 'latency' => int, 'attempts' => int]
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array;
}
