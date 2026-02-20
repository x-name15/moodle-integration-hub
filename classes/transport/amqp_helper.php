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

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;

/**
 * AMQP Helper Class.
 *
 * Centralizes RabbitMQ connection logic, supporting both plain (amqp) and SSL (amqps).
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amqp_helper
{
    /**
     * Parse an AMQP URL and create a connection.
     *
     * @param string $url AMQP URL (e.g., amqp://user:pass@host:5672 or amqps://...)
     * @param int $timeout Connection timeout in seconds.
     * @return AMQPStreamConnection
     * @throws \Exception If URL is invalid or connection fails.
     */
    public static function create_connection(string $url, int $timeout = 5): AMQPStreamConnection {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            throw new \Exception('Invalid AMQP connection string: ' . $url);
        }

        $scheme = $parsed['scheme'] ?? 'amqp';
        $host = $parsed['host'];
        $port = $parsed['port'] ?? ($scheme === 'amqps' ? 5671 : 5672);
        $user = $parsed['user'] ?? 'guest';
        $pass = $parsed['pass'] ?? 'guest';
        $path = isset($parsed['path']) ? $parsed['path'] : '/';

        // Handle path extraction for vhost.
        if ($path !== '/' && strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        $vhost = urldecode($path);
        if ($vhost === '') {
            $vhost = '/';
        }

        if ($scheme === 'amqps') {
            // Standard SSL settings - can be extended with certs if needed.
            $ssloptions = [
                'verify_peer' => false, // Default to false for flexibility, can be strictified.
                'verify_peer_name' => false,
            ];
            return new AMQPSSLConnection(
                $host,
                $port,
                $user,
                $pass,
                $vhost,
                $ssloptions,
                ['connection_timeout' => $timeout, 'read_write_timeout' => $timeout]
            );
        }

        return new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $pass,
            $vhost,
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $timeout,
            $timeout
        );
    }

    /**
     * Ensure a queue exists (loose check).
     *
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     * @param string $queue
     */
    public static function ensure_queue($channel, string $queue): void {
        // durable: true, exclusive: false, auto_delete: false
        $channel->queue_declare($queue, false, true, false, false);
    }
}
