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

header('Content-Type: application/json');

// Only accept POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST.']);
    exit;
}

// 1. Get the service slug.
$serviceslug = optional_param('service', '', PARAM_ALPHANUMEXT);

if (empty($serviceslug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameter: service']);
    exit;
}

// 2. Resolve the service.
$service = service_registry::get_service($serviceslug);
if (!$service) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Service not found']);
    exit;
}

if (empty($service->enabled)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Service is disabled']);
    exit;
}

// 3. Read and parse the JSON logic first.
// We need the raw body for HMAC signature verification and the decoded payload for Schema/Logic.
$rawbody = file_get_contents('php://input'); 
$payload = null;

if (!empty($rawbody)) {
    $payload = json_decode($rawbody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // We don't exit yet, firewall might block this IP regardless of valid JSON.
        // But if we continue, payload is null.
        // Let's allow firewall to decide if it cares about malformed JSON (e.g. rate limit still applies).
    }
}

// --- FIREWALL INSPECTION START ---
if (get_config('local_integrationhub', 'enable_firewall')) {
    try {
        $firewall = new \local_integrationhub\firewall\manager();
        // Pass decoded payload. The guards can also access raw php://input internaly or we could pass raw.
        // Ideally guards should rely on what we pass. 
        // For now, HMAC guard reads input stream again (which might fail if not buffered).
        // Let's rely on the fact that we haven't modified the stream wrapper.
        $firewall->inspect($service, $payload ?? []);
    } catch (\moodle_exception $e) {
        // Determine appropriate HTTP code based on error type?
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => 'Firewall blocked: ' . $e->getMessage()]);
        exit;
    }
}
// --- FIREWALL INSPECTION END ---

// 4. Authenticate — check Bearer token or X-API-Key against the service's auth_token.
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
    echo json_encode(['success' => false, 'error' => 'Invalid authentication token']);
    exit;
}

// 5. Validate JSON if not done/failed before authentication (but after firewall).
if (empty($rawbody)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body']);
    exit;
}

if ($payload === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
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
