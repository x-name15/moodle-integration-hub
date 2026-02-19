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
 * Class ip_whitelist
 *
 * Validates that the request comes from an allowed IP address.
 * Use valid_ip() from Moodle core libraries handling proxy headers correctly.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ip_whitelist implements guard_interface {

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return 'IP Whitelist';
    }

    /**
     * @inheritDoc
     */
    public function inspect(\stdClass $service, array $payload = []): void {
        // If whitelist is empty, allow all.
        if (empty($service->ip_whitelist)) {
            return;
        }

        $allowed_ips = array_map('trim', explode(',', $service->ip_whitelist));
        $remote_ip = getremoteaddr(); // Moodle core function, handles proxies if configured.

        if (empty($remote_ip)) {
             throw new \moodle_exception('accessdenied', 'admin', '', null, 'Unable to determine remote IP address.');
        }

        foreach ($allowed_ips as $allowed) {
            if ($this->ip_match($remote_ip, $allowed)) {
                return; // Match found, pass.
            }
        }

        // No match found.
        throw new \moodle_exception('accessdenied', 'admin', '', null, "Access denied for IP: $remote_ip");
    }

    /**
     * Check if an IP matches a rule (single IP or CIDR).
     *
     * @param string $ip The remote IP.
     * @param string $rule The allowed IP or CIDR.
     * @return bool
     */
    private function ip_match(string $ip, string $rule): bool {
        // IPv6 normalization could be added here if needed.
        
        if (strpos($rule, '/') === false) {
            // Simple string match.
            return $ip === $rule;
        }

        // CIDR match.
        list($subnet, $bits) = explode('/', $rule);
        
        // Handle IPv4 CIDR.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet_long &= $mask; // Sanitize subnet.
            
            return ($ip_long & $mask) == $subnet_long;
        }

        // TODO: Add IPv6 CIDR support if needed.
        return false;
    }
}
