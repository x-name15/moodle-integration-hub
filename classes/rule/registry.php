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

namespace local_integrationhub\rule;

/**
 * Registry for managing event rules.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket - Felix Manrique
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registry
{
    /** @var string Table name. */
    const TABLE = 'local_integrationhub_rules';

    /**
     * Get a rule by ID.
     *
     * @param int $id Rule ID.
     * @return \stdClass
     * @throws \moodle_exception
     */
    public static function get_rule(int $id): \stdClass {
        global $DB;
        $rule = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return $rule;
    }

    /**
     * Get all rules.
     *
     * @return array
     */
    public static function get_all_rules(): array {
        global $DB;
        return $DB->get_records(self::TABLE, null, 'eventname ASC');
    }

    /**
     * Create a new rule.
     *
     * @param \stdClass $data Rule data.
     * @return int New rule ID.
     */
    public static function create_rule(\stdClass $data): int {
        global $DB;

        $rule = new \stdClass();
        $rule->eventname = trim($data->eventname);
        $rule->serviceid = (int)$data->serviceid;
        $rule->endpoint = isset($data->endpoint) ? trim($data->endpoint) : null;
        $rule->http_method = isset($data->http_method) ? strtoupper(trim($data->http_method)) : 'POST';
        $rule->payload_template = isset($data->payload_template) ? trim($data->payload_template) : null;
        $rule->enabled = isset($data->enabled) ? (int)$data->enabled : 1;
        $rule->timecreated = time();
        $rule->timemodified = time();

        return $DB->insert_record(self::TABLE, $rule);
    }

    /**
     * Update an existing rule.
     *
     * @param int $id Rule ID.
     * @param \stdClass $data New data.
     */
    public static function update_rule(int $id, \stdClass $data): void {
        global $DB;

        $rule = self::get_rule($id);

        if (isset($data->eventname)) {
            $rule->eventname = trim($data->eventname);
        }
        if (isset($data->serviceid)) {
            $rule->serviceid = (int)$data->serviceid;
        }
        if (isset($data->endpoint)) {
            $rule->endpoint = trim($data->endpoint);
        }
        if (isset($data->http_method)) {
            $rule->http_method = strtoupper(trim($data->http_method));
        }
        if (isset($data->payload_template)) {
            $rule->payload_template = trim($data->payload_template);
        }
        if (isset($data->enabled)) {
            $rule->enabled = (int)$data->enabled;
        }
        $rule->timemodified = time();

        $DB->update_record(self::TABLE, $rule);
    }

    /**
     * Delete a rule.
     *
     * @param int $id Rule ID.
     */
    public static function delete_rule(int $id): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Get a human-readable name for an event class.
     *
     * @param string $classname
     * @return string
     */
    public static function get_event_display_name(string $classname): string {
        $classname = ltrim($classname, '\\');
        if (class_exists("\\{$classname}")) {
            try {
                $fullclass = "\\{$classname}";
                if (is_a($fullclass, \core\event\base::class, true)) {
                    return $fullclass::get_name();
                }
            } catch (\Exception $e) {
                debugging($e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        $parts = explode('\\', $classname);
        $last = end($parts);
        $pretty = str_replace('_', ' ', $last);
        $pretty = preg_replace('/([a-z])([A-Z])/', '$1 $2', $pretty);

        return ucwords($pretty);
    }

    /**
     * Get all available events in the system dynamically.
     *
     * @return array [classname => Display Name]
     */
    public static function get_all_events_dynamic(): array {
        $events = \core_component::get_component_classes_in_namespace(null, 'event');
        $list = [];

        foreach (array_keys($events) as $event) {
            if (is_a($event, \core\event\base::class, true)) {
                $reflection = new \ReflectionClass($event);
                if (!$reflection->isAbstract()) {
                    $list["\\{$event}"] = self::get_event_display_name($event);
                }
            }
        }

        asort($list);
        return $list;
    }

    /**
     * Get a list of common events for the dropdown.
     *
     * @return array [classname => Display Name]
     */
    public static function get_common_events(): array {
        $common = [
            '\core\event\user_created',
            '\core\event\user_loggedin',
            '\core\event\course_created',
            '\core\event\user_enrolment_created',
            '\core\event\user_graded',
        ];

        $list = [];
        foreach ($common as $classname) {
            $list[$classname] = self::get_event_display_name($classname);
        }
        return $list;
    }
}
