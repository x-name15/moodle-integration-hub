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

namespace local_integrationhub\firewall;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface guard_interface
 *
 * All firewall rules must implement this interface.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface guard_interface {

    /**
     * Inspect the incoming request.
     *
     * @param \stdClass $service The service configuration object.
     * @param array     $payload The decoded JSON payload (optional).
     * @return void
     * @throws \moodle_exception If the check fails (blocks the request).
     */
    public function inspect(\stdClass $service, array $payload = []): void;

    /**
     * Get the name of the guard for logging.
     *
     * @return string
     */
    public function get_name(): string;
}
