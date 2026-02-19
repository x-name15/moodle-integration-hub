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
 * Class hmac_guard
 *
 * Verifies that the request payload matches the provided HMAC signature.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hmac_guard implements guard_interface {

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return 'HMAC Signature Verification';
    }

    /**
     * @inheritDoc
     */
    public function inspect(\stdClass $service, array $payload = []): void {
        // If no secret configured, skip.
        if (empty($service->hmac_secret)) {
            return;
        }

        $algo = $service->hmac_algo ?? 'sha256';
        $header_name = $service->hmac_header ?? 'X-Hub-Signature-256';

        // Retrieve the signature from headers.
        $signature = $this->get_header($header_name);

        if (empty($signature)) {
            throw new \moodle_exception('accessdenied', 'admin', '', null, "Missing signature header: $header_name");
        }

        // Re-encode payload to JSON to verify signature. 
        // NOTE: In a perfect world, we'd pass the raw body string to inspect(), 
        // but for now we re-encode. Ideally webhook.php should pass raw body too.
        // If payload structure changed by json_decode/encode (whitespace), this might fail.
        // For strict HMAC, we need the RAW body.
        // Accessing global input stream again might not work if it was already read.
        // We will assume webhook.php has not corrupted the input or we can read php://input again if needed?
        // Actually, reading php://input twice works in some SAPI regular setups but not always.
        // Let's try to get the raw body from a global cache or read it if possible.
        
        $raw_body = file_get_contents('php://input'); 

        $calculated = hash_hmac($algo, $raw_body, $service->hmac_secret);

        // Handle cases where signature might be prefixed (e.g. "sha256=...")
        $expected = $calculated;
        
        // Some services send "sha256=signature". We check if provided signature contains the hash.
        // Only strip if strictly required? No, let's compare as is, or check if user config includes prefix.
        // Standardize: most users will paste just the key.
        // GitHub sends: `sha256=...`
        // We compare strict equality first.
        
        if (!hash_equals($expected, $signature)) {
            // Try prefixing with algo if simple match fails
            if (!hash_equals("$algo=$expected", $signature)) {
                 throw new \moodle_exception('accessdenied', 'admin', '', null, "Invalid HMAC signature.");
            }
        }
    }

    /**
     * Helper to get header value case-insensitively.
     */
    private function get_header(string $name): ?string {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    return $value;
                }
            }
        }
        
        // Fallback to $_SERVER
        $skey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$skey] ?? null;
    }
}
