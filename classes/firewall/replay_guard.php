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
 * Class replay_guard
 *
 * Prevents replay attacks by verifying timestamp freshness and nonce uniqueness.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replay_guard implements guard_interface {

    /** @var int Max age of a request in seconds (default 5 mins) */
    const MAX_AGE = 300;

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return 'Replay Protection';
    }

    /**
     * @inheritDoc
     */
    public function inspect(\stdClass $service, array $payload = []): void {
        global $DB;

        // Check headers for timestamp and nonce. 
        // We support standard headers:
        // X-Request-Timestamp / X-GitHub-Delivery / X-Nonce
        
        $timestamp = $this->get_header('X-Request-Timestamp');
        $nonce = $this->get_header('X-Nonce') ?? $this->get_header('X-GitHub-Delivery'); // GitHub sends Delivery ID as nonce

        // If headers missing, assume not enforcing replay protection unless strict mode enabled?
        // Ideally we should enforce if service has enabled it explicitly.
        // For now, if no timestamp/nonce is provided, we skip (open by default for backward compat).
        if (empty($timestamp) || empty($nonce)) {
            return;
        }

        // 1. Check Timestamp Freshness
        $now = time();
        if (abs($now - (int)$timestamp) > self::MAX_AGE) {
            throw new \moodle_exception('accessdenied', 'admin', '', null, "Request timestamp too old or in future.");
        }

        // 2. Check Nonce Uniqueness
        if ($DB->record_exists('local_integrationhub_nonce', ['serviceid' => $service->id, 'nonce' => $nonce])) {
            throw new \moodle_exception('accessdenied', 'admin', '', null, "Replay detected: Nonce already used.");
        }

        // 3. Store Nonce
        $record = new \stdClass();
        $record->serviceid = $service->id;
        $record->nonce = $nonce;
        $record->timestamp = (int)$timestamp;
        $record->timecreated = $now;
        
        try {
            $DB->insert_record('local_integrationhub_nonce', $record);
        } catch (\dml_exception $e) {
            // Race condition: another request inserted same nonce just now.
            throw new \moodle_exception('accessdenied', 'admin', '', null, "Replay detected: Race condition on nonce.");
        }
    }

    private function get_header(string $name): ?string {
        // ... (Same helper as hmac_guard, consider moving to base class or trait)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    return $value;
                }
            }
        }
        $skey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$skey] ?? null;
    }
}
