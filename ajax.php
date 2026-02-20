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
 * AJAX endpoint for the Integration Hub plugin.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

define('AJAX_SCRIPT', true);

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

require_login();
$context = context_system::instance();
require_capability('local/integrationhub:manage', $context);

header('Content-Type: application/json');

if ($action === 'preview_payload') {
    $template = optional_param('template', '', PARAM_RAW);
    $eventname = optional_param('eventname', '', PARAM_RAW);

    // Mock data for interpolation.
    $mockdata = [
        'eventname' => $eventname ?: '\core\event\course_created',
        'objectid' => 123,
        'userid' => 5,
        'courseid' => 10,
        'contextid' => 1,
        'timecreated' => time(),
        'ip' => '127.0.0.1',
    ];

    $json = $template;
    foreach ($mockdata as $key => $value) {
        $replacement = $value;
        if (is_string($value)) {
            $replacement = substr(json_encode($value), 1, -1);
        }
        $json = str_replace('{{' . $key . '}}', $replacement, $json);
    }

    $decoded = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => json_last_error_msg(),
            'raw' => $json,
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'payload' => $decoded,
            'raw' => $json,
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
