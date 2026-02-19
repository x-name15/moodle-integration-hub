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
 * Class manager
 *
 * Orchestrates the execution of firewall guards.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var guard_interface[] List of guards to execute. */
    protected $guards = [];

    /**
     * Initialize the firewall with standard guards.
     */
    public function __construct() {
        // Order matters: efficient checks first (IP), then heavy checks (Schema).
        $this->guards = [
            new ip_whitelist(),
            new rate_limiter(),
            new hmac_guard(),
            new replay_guard(),
            // new schema_validator(), // TODO: Implement schema validator
        ];
    }

    /**
     * Run all firewall checks for a given service.
     *
     * @param \stdClass $service The service configuration.
     * @param array     $payload The request payload.
     * @return void
     * @throws \moodle_exception If any guard fails.
     */
    public function inspect(\stdClass $service, array $payload = []): void {
        foreach ($this->guards as $guard) {
            try {
                $guard->inspect($service, $payload);
            } catch (\moodle_exception $e) {
                // Re-throw with context or log if needed.
                // For now, we let the exception bubble up to the webhook handler.
                // We could add specific logging here if we wanted "Firewall Blocked" logs distinct from "Errors".
                throw $e;
            }
        }
    }
}
