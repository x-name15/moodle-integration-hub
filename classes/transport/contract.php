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

/**
 * Interface contract.
 *
 * Defines the method signature for all transport drivers (HTTP, AMQP, etc).
 */
interface contract
{
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