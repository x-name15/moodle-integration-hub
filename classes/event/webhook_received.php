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

/**
 * Event fired when an external service sends data back into Moodle via MIH.
 *
 * Other plugins can observe this event to process inbound data:
 *   $observers = [
 *       ['eventname' => '\local_integrationhub\event\webhook_received',
 *        'callback'  => 'my_plugin\observer::handle_webhook'],
 *   ];
 *
 * The 'other' data contains:
 *   - serviceid   (int)    The source service ID.
 *   - servicename (string) The source service slug.
 *   - source      (string) 'webhook' or 'amqp'.
 *   - payload     (array)  The decoded JSON payload.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook_received extends \core\event\base
{
    /**
     * Initialise event properties.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('webhook_received', 'local_integrationhub');
    }

    /**
     * Returns event description.
     *
     * @return string
     */
    public function get_description() {
        $servicename = $this->other['servicename'] ?? 'unknown';
        $source = $this->other['source'] ?? 'unknown';
        return "Inbound {$source} received from service '{$servicename}'.";
    }

    /**
     * Custom validation.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['serviceid'])) {
            throw new \coding_exception('The \'serviceid\' value must be set in other.');
        }
    }
}
