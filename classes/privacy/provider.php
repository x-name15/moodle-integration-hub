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

/**
 * Privacy Subsystem for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_integrationhub\privacy;

/**
 * Privacy provider for the Integration Hub plugin.
 *
 * The Integration Hub does not store personal user data directly.
 * It acts as a gateway and only logs technical data required for
 * monitoring integrations. The administrator is responsible for
 * configuring compliant external integrations.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider
{
    /**
     * Get the language string identifier with a description of what data this plugin stores.
     *
     * @return string The language string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
