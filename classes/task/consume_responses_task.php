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

namespace local_integrationhub\task;

defined('MOODLE_INTERNAL') || die();

use PhpAmqpLib\Connection\AMQPStreamConnection;
use local_integrationhub\webhook_handler;
use local_integrationhub\service\registry as service_registry;
use local_integrationhub\transport\amqp_helper;

/**
 * Scheduled task to consume response messages from AMQP queues.
 *
 * For each AMQP-type service that has a response_queue configured,
 * this task connects and polls for messages using basic_get (non-blocking).
 * Each message is processed via the shared webhook_handler.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consume_responses_task extends \core\task\scheduled_task
{
    /** @var int Maximum messages to consume per service per run. */
    const MAX_MESSAGES_PER_RUN = 50;

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('task_consume_responses', 'local_integrationhub');
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;

        // Get all AMQP services with a response_queue configured.
        $services = $DB->get_records_select(
            'local_integrationhub_svc',
            "type = ? AND response_queue IS NOT NULL AND response_queue != '' AND enabled = 1",
            ['amqp']
        );

        if (empty($services)) {
            mtrace('  MIH Consumer: No AMQP services with response queues configured.');
            return;
        }

        foreach ($services as $service) {
            $this->consume_from_service($service);
        }
    }

    /**
     * Consume messages from a single service's response queue.
     *
     * @param \stdClass $service The service record.
     */
    private function consume_from_service(\stdClass $service): void
    {
        $queue = trim($service->response_queue);
        mtrace("  MIH Consumer [{$service->name}]: Consuming from queue '{$queue}'...");

        try {
            $connection = amqp_helper::create_connection($service->base_url, (int)($service->timeout ?: 5));
            $channel = $connection->channel();

            // Ensure queue exists before consuming.
            amqp_helper::ensure_queue($channel, $queue);

            $consumed = 0;

            // Non-blocking poll: basic_get instead of basic_consume.
            while ($consumed < self::MAX_MESSAGES_PER_RUN) {
                $msg = $channel->basic_get($queue, false);
                if ($msg === null) {
                    // No more messages.
                    break;
                }

                $body = $msg->body;
                $payload = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    mtrace("    Skipping non-JSON message: " . json_last_error_msg());
                    $channel->basic_ack($msg->getDeliveryTag());
                    $consumed++;
                    continue;
                }

                // Process via shared handler.
                $result = webhook_handler::handle($service, $payload, 'amqp');

                if ($result['success']) {
                    $channel->basic_ack($msg->getDeliveryTag());
                    $consumed++;
                } else {
                    // Negative-ack with requeue so the message isn't lost.
                    $channel->basic_nack($msg->getDeliveryTag(), false, true);
                    mtrace("    Error processing message: " . $result['error']);
                    break; // Stop consuming from this service on error.
                }
            }

            $channel->close();
            $connection->close();

            mtrace("    Consumed {$consumed} message(s).");
        } catch (\Exception $e) {
            mtrace("  MIH Consumer [{$service->name}]: Error: " . $e->getMessage());
        }
    }
}
