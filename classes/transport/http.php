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

        // Add custom headers if configured.
        if (!empty($service->custom_headers)) {
            $customheaders = json_decode($service->custom_headers, true);
            if (is_array($customheaders)) {
                foreach ($customheaders as $key => $value) {
                    $headers[] = $key . ': ' . $value;
                }
            }
        }

        $jsonpayload = !empty($payload) ? json_encode($payload) : '';
        $method = strtoupper($method) ?: 'POST';

        // Use Moodle's curl wrapper to respect proxy settings and security controls.
        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => (int)$service->timeout,
            'CURLOPT_CONNECTTIMEOUT' => min((int)$service->timeout, 10),
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        // Set request headers.
        foreach ($headers as $header) {
            $curl->setHeader($header);
        }

        // Dispatch by method.
        try {
            switch ($method) {
                case 'POST':
                    $resp = $curl->post($url, $jsonpayload);
                    break;
                case 'PUT':
                    $resp = $curl->put($url, ['data' => $jsonpayload]);
                    break;
                case 'PATCH':
                    $resp = $curl->patch($url, $jsonpayload);
                    break;
                case 'DELETE':
                    $resp = $curl->delete($url, ['data' => $jsonpayload]);
                    break;
                case 'GET':
                default:
                    // For GET, append payload as query parameters.
                    if (!empty($payload)) {
                        $url = $this->build_url_with_query($url, $payload);
                    }
                    $resp = $curl->get($url);
                    break;
            }

            $curlerr = $curl->get_errno();
            $httpcode = $curl->info['http_code'] ?? 0;

            if ($curlerr) {
                throw new \Exception("cURL error {$curlerr} for {$url}: " . $curl->error);
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

    /**
     * Build URL with query parameters for GET requests.
     *
     * @param string $url Base URL
     * @param array $params Query parameters
     * @return string URL with query string appended
     */
    private function build_url_with_query(string $url, array $params): string {
        if (empty($params)) {
            return $url;
        }

        $querystring = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $separator = (strpos($url, '?') === false) ? '?' : '&';

        return $url . $separator . $querystring;
    }
}
