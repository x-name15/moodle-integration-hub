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
 * Class rate_limiter
 *
 * Limits the number of requests a service can make within a time window.
 * Uses MUC (Moodle Universal Cache) for storage.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter implements guard_interface {

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return 'Rate Limiting';
    }

    /**
     * @inheritDoc
     */
    public function inspect(\stdClass $service, array $payload = []): void {
        if (empty($service->rate_limit_requests) || $service->rate_limit_requests <= 0) {
            return;
        }

        $limit = (int)$service->rate_limit_requests;
        $window = (int)($service->rate_limit_window ?? 60);

        // Cache key: service_id + time_window_bucket
        // Simple Fixed Window algorithm.
        $bucket = floor(time() / $window);
        $key = "ratelimit_{$service->id}_{$bucket}";

        // cache::make('component', 'definition');
        $cache = \cache::make('local_integrationhub', 'rate_limit'); 

        $count = $cache->get($key);
        if ($count === false) {
            $count = 0;
        }

        if ($count >= $limit) {
            // Throw exception with 429 hint (handled by caller ideally)
             throw new \moodle_exception('accessdenied', 'admin', '', null, "Rate limit exceeded. Try again later.");
        }

        $cache->set($key, $count + 1, $window);
    }
}
