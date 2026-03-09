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

namespace local_integrationhub;

/**
 * Rate Limiter — prevents abuse by limiting request frequency.
 *
 * Uses Moodle's cache system to implement sliding window rate limiting.
 * Protects webhook endpoints from DoS attacks and brute force attempts.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter
{
    /**
     * Check if a request is allowed based on rate limits.
     *
     * Uses a sliding window algorithm to track request counts.
     * Each identifier (e.g., IP+service) has its own counter that resets
     * after the time window expires.
     *
     * @param string $identifier Unique identifier (e.g., 'webhook_192.168.1.1_myservice').
     * @param int $maxrequests Maximum requests allowed in the time window.
     * @param int $windowseconds Time window in seconds.
     * @return bool True if request is allowed, false if rate limit exceeded.
     */
    public static function is_allowed(
        string $identifier,
        int $maxrequests = 60,
        int $windowseconds = 60
    ): bool {
        $cache = \cache::make('local_integrationhub', 'rate_limit');
        $key = 'ratelimit_' . md5($identifier);

        $data = $cache->get($key);
        $now = time();

        if (!$data) {
            // First request from this identifier.
            $cache->set($key, [
                'count' => 1,
                'window_start' => $now,
            ]);
            return true;
        }

        // Check if the time window has expired.
        if (($now - $data['window_start']) >= $windowseconds) {
            // Window expired, reset counter.
            $cache->set($key, [
                'count' => 1,
                'window_start' => $now,
            ]);
            return true;
        }

        // Within the time window: check if limit exceeded.
        if ($data['count'] >= $maxrequests) {
            return false; // BLOCKED - rate limit exceeded.
        }

        // Increment counter.
        $data['count']++;
        $cache->set($key, $data);
        return true;
    }

    /**
     * Get the number of remaining requests for an identifier.
     *
     * @param string $identifier Unique identifier.
     * @param int $maxrequests Maximum requests allowed.
     * @return int Number of remaining requests.
     */
    public static function get_remaining(string $identifier, int $maxrequests = 60): int {
        $cache = \cache::make('local_integrationhub', 'rate_limit');
        $key = 'ratelimit_' . md5($identifier);

        $data = $cache->get($key);

        if (!$data) {
            return $maxrequests;
        }

        return max(0, $maxrequests - ($data['count'] ?? 0));
    }

    /**
     * Reset rate limit for a specific identifier.
     *
     * Useful for administrative actions or debugging.
     *
     * @param string $identifier Unique identifier to reset.
     * @return bool True on success.
     */
    public static function reset(string $identifier): bool {
        $cache = \cache::make('local_integrationhub', 'rate_limit');
        $key = 'ratelimit_' . md5($identifier);
        return $cache->delete($key);
    }
}
