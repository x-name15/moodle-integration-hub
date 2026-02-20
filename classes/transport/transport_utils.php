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

namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait for common transport utilities.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait transport_utils
{
    /**
     * Format a success response.
     *
     * @param mixed $response Body/Response data.
     * @param float $starttime Microtime start.
     * @param int   $attempts Number of attempts.
     * @param int   $httpcode Optional HTTP status code (or equivalent).
     * @return array
     */
    protected function success_result($response, float $starttime, int $attempts = 1, int $httpcode = 200): array
    {
        return [
            'success' => true,
            'response' => $response,
            'error' => null,
            'latency' => (int)((microtime(true) - $starttime) * 1000),
            'attempts' => $attempts,
            'http_code' => $httpcode,
        ];
    }

    /**
     * Format an error response.
     *
     * @param string $error Error message.
     * @param float  $starttime Microtime start.
     * @param int    $attempts Number of attempts.
     * @param int    $httpcode Optional HTTP status code (or equivalent).
     * @return array
     */
    protected function error_result(string $error, float $starttime, int $attempts = 1, int $httpcode = 0): array
    {
        return [
            'success' => false,
            'response' => null,
            'error' => $error,
            'latency' => (int)((microtime(true) - $starttime) * 1000),
            'attempts' => $attempts,
            'http_code' => $httpcode,
        ];
    }
}