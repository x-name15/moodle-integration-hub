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
 * CLI script to test MIH facade operations locally.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$system = \context_system::instance();

mtrace("---------------------------------------------------");
mtrace("Testing MIH Facade...");
mtrace("---------------------------------------------------");

// Test 1: Static Request
try {
    mtrace("1. Testing mih::request('test-service')...");
    \local_integrationhub\mih::request('test-service-static', '/api', ['a' => 1]);
}
catch (\moodle_exception $e) {
    // We expect service_not_found if it doesn't exist.
    if ($e->errorcode === 'service_not_found') {
        mtrace("   [PASS] MIH handled request directly (Service check failed as expected).");
    }
    else {
        mtrace("   [FAIL] Unexpected error: " . $e->getMessage());
    }
}
catch (\Exception $e) {
    mtrace("   [FAIL] Exception: " . $e->getMessage());
}

// Test 2: Fluent Interface
try {
    mtrace("2. Testing mih::send('test-service')...");
    \local_integrationhub\mih::send('test-service-fluent')
        ->to('/api/v2')
        ->with(['b' => 2])
        ->method('PUT')
        ->dispatch();
}
catch (\moodle_exception $e) {
    if ($e->errorcode === 'service_not_found') {
        mtrace("   [PASS] Fluent chain executed correctly via MIH.");
    }
    else {
        mtrace("   [FAIL] Unexpected error: " . $e->getMessage());
    }
}
catch (\Error $e) {
    mtrace("   [FAIL] PHP Error (Interface mismatch?): " . $e->getMessage());
}

mtrace("---------------------------------------------------");
mtrace("Done.");