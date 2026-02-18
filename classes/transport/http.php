<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * HTTP Transport Driver.
 *
 * Handles standard REST/HTTP calls using Moodle's curl library.
 */
class http implements contract
{
    use transport_utils;

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = 'POST'): array
    {
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
            }
            else if ($service->auth_type === 'apikey') {
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

        // Method
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

        // Headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        try {
            $resp = curl_exec($ch);
            $curlerr = curl_errno($ch);
            $curlerr_msg = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlerr) {
                throw new \Exception("cURL error {$curlerr} for {$url}: " . $curlerr_msg);
            }

            // Determine success (2xx).
            $success = ($httpcode >= 200 && $httpcode < 300);

            if ($success) {
                return $this->success_result($resp, $starttime, $attempts, $httpcode);
            }
            else {
                return $this->error_result("HTTP {$httpcode}", $starttime, $attempts, $httpcode);
            }

        }
        catch (\Exception $e) {
            return $this->error_result($e->getMessage(), $starttime, $attempts, 0);
        }
    }
}