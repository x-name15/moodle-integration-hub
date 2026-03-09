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

/**
 * Inbound webhook endpoint for Integration Hub.
 *
 * External services POST JSON to this endpoint to push data back into Moodle.
 * Authentication is via the service's own auth_token (Bearer or X-API-Key).
 *
 * Usage:
 *   POST /local/integrationhub/webhook.php?service=my_service_slug
 *   Headers: Authorization: Bearer <token>
 *   Body: {"key": "value"}
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This endpoint does NOT require Moodle login — auth is via service token.
define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');

use local_integrationhub\service\registry as service_registry;
use local_integrationhub\webhook_handler;
use local_integrationhub\rate_limiter;

header('Content-Type: application/json');

// Only accept POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => get_string('webhook_method_not_allowed', 'local_integrationhub')]);
    exit;
}

// 1. Get the service slug.
$serviceslug = optional_param('service', '', PARAM_ALPHANUMEXT);

if (empty($serviceslug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => get_string('webhook_missing_service', 'local_integrationhub')]);
    exit;
}

// 1.5. Apply rate limiting to prevent abuse.
$clientip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$identifier = "webhook_{$clientip}_{$serviceslug}";

// Get rate limit configuration from settings.
$ratelimit = (int)get_config('local_integrationhub', 'webhook_rate_limit');
if ($ratelimit <= 0) {
    $ratelimit = 100; // Default: 100 requests per window.
}
$ratewindow = (int)get_config('local_integrationhub', 'webhook_rate_window');
if ($ratewindow <= 0) {
    $ratewindow = 60; // Default: 60 seconds.
}

if (!rate_limiter::is_allowed($identifier, $ratelimit, $ratewindow)) {
    http_response_code(429); // Too Many Requests.
    $remaining = rate_limiter::get_remaining($identifier, $ratelimit);

    header('X-RateLimit-Limit: ' . $ratelimit);
    header('X-RateLimit-Remaining: ' . $remaining);
    header('Retry-After: ' . $ratewindow);

    echo json_encode([
        'success' => false,
        'error' => get_string('webhook_rate_limited', 'local_integrationhub'),
    ]);
    exit;
}

// 2. Resolve the service.
$service = service_registry::get_service($serviceslug);
if (!$service) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => get_string('webhook_invalid_service', 'local_integrationhub')]);
    exit;
}

if (empty($service->enabled)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => get_string('webhook_service_disabled', 'local_integrationhub')]);
    exit;
}

// 3. Authenticate — check Bearer token or X-API-Key against the service's auth_token.
$authenticated = false;
$expectedtoken = trim($service->auth_token ?? '');

if (!empty($expectedtoken)) {
    // Try Authorization header.
    $authheader = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authheader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    } else if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authheader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    // Check Bearer token.
    if (preg_match('/^Bearer\s+(.+)$/i', $authheader, $matches)) {
        if (hash_equals($expectedtoken, trim($matches[1]))) {
            $authenticated = true;
        }
    }

    // Check X-API-Key header as fallback.
    if (!$authenticated) {
        $apikey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apikey) && hash_equals($expectedtoken, trim($apikey))) {
            $authenticated = true;
        }
    }
} else {
    // No token configured — allow (open webhook). Not recommended for production.
    $authenticated = true;
}

if (!$authenticated) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => get_string('webhook_invalid_token', 'local_integrationhub')]);
    exit;
}

// 4. Read and parse the JSON body.
$rawbody = file_get_contents('php://input');
if (empty($rawbody)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => get_string('webhook_empty_body', 'local_integrationhub')]);
    exit;
}

$payload = json_decode($rawbody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $errormsg = get_string('webhook_invalid_json', 'local_integrationhub', json_last_error_msg());
    echo json_encode(['success' => false, 'error' => $errormsg]);
    exit;
}

// 5. Dispatch to shared handler.
$result = webhook_handler::handle($service, $payload, 'webhook');

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
