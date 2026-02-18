<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait for common transport utilities.
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