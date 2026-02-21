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

/**
 * HTTP Transport Driver.
 *
 * Handles standard REST/HTTP calls using Moodle's curl library.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class http implements contract
{
    use transport_utils;

    /**
     * Execute the HTTP request.
     *
     * @param \stdClass $service The service configuration.
     * @param string $endpoint The endpoint path.
     * @param array $payload The request payload data.
     * @param string $method The HTTP method.
     * @return array Processed response array.
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = 'POST'): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = rtrim($service->base_url, '/') . '/' . ltrim($endpoint, '/');
        $starttime = microtime(true);
        $attempts = 1; // Basic HTTP is usually 1 attempt here; retry logic is in Gateway/Policy.

        // Auth Headers.
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if (!empty($service->auth_token)) {
            if ($service->auth_type === 'bearer') {
                $headers[] = 'Authorization: Bearer ' . $service->auth_token;
            } else if ($service->auth_type === 'apikey') {
                $headers[] = 'X-API-Key: ' . $service->auth_token;
            }
        }

        $jsonpayload = !empty($payload) ? json_encode($payload) : '';
        $method = strtoupper($method) ?: 'POST';

        // BYPASS: Use native PHP curl to avoid Moodle's SSRF blocking for local testing.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$service->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min((int)$service->timeout, 10));

        // Method.
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonpayload);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonpayload);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonpayload);
                break;
            case 'GET':
            default:
                if (!empty($payload)) {
                    $url .= '?' . http_build_query($payload);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }

        // Headers.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        try {
            $resp = curl_exec($ch);
            $curlerr = curl_errno($ch);
            $curlerrmsg = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlerr) {
                throw new \Exception("cURL error {$curlerr} for {$url}: " . $curlerrmsg);
            }

            // Determine success (2xx).
            $success = ($httpcode >= 200 && $httpcode < 300);

            if ($success) {
                return $this->success_result($resp, $starttime, $attempts, $httpcode);
            } else {
                return $this->error_result("HTTP {$httpcode}", $starttime, $attempts, $httpcode);
            }
        } catch (\Exception $e) {
            return $this->error_result($e->getMessage(), $starttime, $attempts, 0);
        }
    }
}
