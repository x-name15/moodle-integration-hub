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

namespace local_integrationhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for Integration Hub.
 *
 * Listens to Moodle events and dispatches them to external services based on configured rules.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer
{
    /**
     * Handle observed events.
     *
     * @param \core\event\base $event The Moodle event.
     */
    public static function handle_event(\core\event\base $event)
    {
        global $DB;
        $eventname = $event->eventname; // E.g., \core\event\user_created.
        $rules = $DB->get_records('local_integrationhub_rules', [
            'eventname' => $eventname,
            'enabled' => 1,
        ]);

        if (empty($rules)) {
            return;
        }

        $cache = \cache::make('local_integrationhub', 'event_dedupe');
        $sigdata = [
            'name' => $eventname,
            'obj' => $event->objectid,
            'user' => $event->userid,
            'rel' => $event->relateduserid,
            'crud' => $event->crud,
        ];
        $signature = sha1(json_encode($sigdata));

        if ($cache->get($signature)) {
            return;
        }
        $dispatched = false;
        foreach ($rules as $rule) {
            $task = new \local_integrationhub\task\dispatch_event_task();
            $task->set_custom_data([
                'ruleid' => $rule->id,
                'eventdata' => $event->get_data(),
                'eventcontextid' => $event->contextid,
            ]);
            \core\task\manager::queue_adhoc_task($task);
            $dispatched = true;
        }
        if ($dispatched) {
            $cache->set($signature, 1);
        }
    }
}