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

namespace local_integrationhub\service;

/**
 * Service Registry — CRUD operations for registered services.
 *
 * Provides the data layer for managing external services stored in
 * the local_integrationhub_svc table.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket - Felix Manrique
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registry
{
    /** @var string Table name for services. */
    const TABLE = 'local_integrationhub_svc';

    /**
     * Get a service record by its unique name (slug).
     *
     * @param string $name The service name.
     * @return \stdClass|false The service record, or false if not found.
     */
    public static function get_service(string $name) {
        global $DB;
        return $DB->get_record(self::TABLE, ['name' => $name]);
    }

    /**
     * Get a service record by its ID.
     *
     * @param int $id The service ID.
     * @return \stdClass The service record.
     * @throws \dml_exception If not found.
     */
    public static function get_service_by_id(int $id): \stdClass {
        global $DB;
        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get all registered services.
     *
     * @return \stdClass[] Array of service records.
     */
    public static function get_all_services(): array {
        global $DB;
        return $DB->get_records(self::TABLE, null, 'name ASC');
    }

    /**
     * Create a new service.
     *
     * @param \stdClass $data Service data with fields: name, base_url, auth_type, auth_token,
     *                        timeout, max_retries, retry_backoff, cb_failure_threshold, cb_cooldown.
     * @return int The new service ID.
     * @throws \dml_exception
     */
    public static function create_service(\stdClass $data): int {
        global $DB;

        $now = time();
        $record = new \stdClass();
        $record->name = clean_param($data->name, PARAM_TEXT);
        $record->type = clean_param($data->type ?? 'rest', PARAM_ALPHA);
        $record->base_url = clean_param($data->base_url, PARAM_RAW_TRIMMED); // Allow amqp:// schemes.
        $record->auth_type = clean_param($data->auth_type ?? 'bearer', PARAM_ALPHA);
        $record->auth_token = $data->auth_token ?? '';
        $record->timeout = (int)($data->timeout ?? 5);
        $record->max_retries = (int)($data->max_retries ?? 3);
        $record->retry_backoff = (int)($data->retry_backoff ?? 1);
        $record->cb_failure_threshold = (int)($data->cb_failure_threshold ?? 5);
        $record->cb_cooldown = (int)($data->cb_cooldown ?? 30);
        $record->response_queue = clean_param($data->response_queue ?? '', PARAM_ALPHANUMEXT);
        $record->enabled = 1;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $id = $DB->insert_record(self::TABLE, $record);
        $cb = new \stdClass();
        $cb->serviceid = $id;
        $cb->state = 'closed';
        $cb->failure_count = 0;
        $cb->last_failure = null;
        $cb->timemodified = $now;
        $DB->insert_record('local_integrationhub_cb', $cb);

        return $id;
    }

    /**
     * Update an existing service.
     *
     * @param int $id The service ID.
     * @param \stdClass $data Updated fields.
     * @return bool True on success.
     * @throws \dml_exception
     */
    public static function update_service(int $id, \stdClass $data): bool {
        global $DB;

        $record = self::get_service_by_id($id);

        if (isset($data->name)) {
            $record->name = clean_param($data->name, PARAM_TEXT);
        }
        if (isset($data->type)) {
            $record->type = clean_param($data->type, PARAM_ALPHA);
        }
        if (isset($data->base_url)) {
            $record->base_url = clean_param($data->base_url, PARAM_RAW_TRIMMED);
        }
        if (isset($data->auth_type)) {
            $record->auth_type = clean_param($data->auth_type, PARAM_ALPHA);
        }
        if (isset($data->auth_token)) {
            $record->auth_token = $data->auth_token;
        }
        if (isset($data->timeout)) {
            $record->timeout = (int)$data->timeout;
        }
        if (isset($data->max_retries)) {
            $record->max_retries = (int)$data->max_retries;
        }
        if (isset($data->retry_backoff)) {
            $record->retry_backoff = (int)$data->retry_backoff;
        }
        if (isset($data->cb_failure_threshold)) {
            $record->cb_failure_threshold = (int)$data->cb_failure_threshold;
        }
        if (isset($data->cb_cooldown)) {
            $record->cb_cooldown = (int)$data->cb_cooldown;
        }
        if (isset($data->enabled)) {
            $record->enabled = (int)$data->enabled;
        }
        if (isset($data->response_queue)) {
            $record->response_queue = clean_param($data->response_queue, PARAM_ALPHANUMEXT);
        }

        $record->timemodified = time();

        return $DB->update_record(self::TABLE, $record);
    }

    /**
     * Delete a service and its associated circuit breaker state.
     *
     * @param int $id The service ID.
     * @return bool True on success.
     */
    public static function delete_service(int $id): bool {
        global $DB;
        $DB->delete_records('local_integrationhub_cb', ['serviceid' => $id]);
        $DB->delete_records('local_integrationhub_log', ['serviceid' => $id]);
        $DB->delete_records('local_integrationhub_rules', ['serviceid' => $id]);
        $DB->delete_records('local_integrationhub_dlq', ['serviceid' => $id]);

        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
