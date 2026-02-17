<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * HTTP Transport Driver.
 *
 * Handles standard REST/HTTP calls using Moodle's curl library.
 */
class http implements contract {

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = 'POST'): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $url = rtrim($service->base_url, '/') . '/' . ltrim($endpoint, '/');
        $starttime = microtime(true);
        $attempts = 1; // Basic HTTP is usually 1 attempt here; retry logic is in Gateway/Policy.

        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => (int)$service->timeout,
            'CURLOPT_CONNECTTIMEOUT' => min((int)$service->timeout, 10),
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        // Auth Headers.
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if (!empty($service->auth_token)) {
            if ($service->auth_type === 'bearer') {
                $headers[] = 'Authorization: Bearer ' . $service->auth_token;
            } else if ($service->auth_type === 'apikey') {
                $headers[] = 'X-API-Key: ' . $service->auth_token;
            }
        }
        $curl->setHeader($headers);

        $jsonpayload = !empty($payload) ? json_encode($payload) : '';
        $method = strtoupper($method) ?: 'POST';

        try {
            switch ($method) {
                case 'GET':
                    $resp = $curl->get($url, $payload);
                    break;
                case 'POST':
                    $resp = $curl->post($url, $jsonpayload);
                    break;
                case 'PUT':
                    $resp = $curl->put($url, $jsonpayload);
                    break;
                case 'DELETE':
                    $resp = $curl->delete($url, $payload);
                    break;
                default:
                    throw new \moodle_exception('Unsupported HTTP method: ' . $method);
            }

            $info = $curl->get_info();
            $httpcode = $info['http_code'] ?? 0;
            $curlerr = $curl->get_errno();

            if ($curlerr) {
                throw new \Exception("cURL error {$curlerr} for {$url}: " . $curl->error);
            }

            // Determine success (2xx).
            $success = ($httpcode >= 200 && $httpcode < 300);
            $error = $success ? null : "HTTP {$httpcode}";

            return [
                'success'   => $success,
                'response'  => $resp,
                'http_code' => $httpcode, // Extra metadata for HTTP
                'error'     => $error,
                'latency'   => (int)((microtime(true) - $starttime) * 1000),
                'attempts'  => $attempts
            ];

        } catch (\Exception $e) {
            return [
                'success'   => false,
                'response'  => null,
                'http_code' => 0,
                'error'     => $e->getMessage(),
                'latency'   => (int)((microtime(true) - $starttime) * 1000),
                'attempts'  => $attempts
            ];
        }
    }
}
