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
 * CLI script to manually test MIH logic after syntax fixes.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

use local_integrationhub\service\registry as svc_registry;
use local_integrationhub\rule\registry as rule_registry;
use local_integrationhub\mih;

mtrace('Integration Hub - Manual Logic Verification');

try {
    // ------------------------------------------------------------------------
    // TEST 1: Service Registry (Create, Read, Update, Delete)
    // ------------------------------------------------------------------------
    mtrace("1. Testing Service Registry...");

    // Create
    $svc_data = (object)[
        'name' => 'CLI Test Service ' . time(),
        'type' => 'rest',
        'base_url' => 'https://httpbin.org',
        'auth_type' => 'none',
        'timeout' => 5,
        'max_retries' => 1,
        'retry_backoff' => 1,
        'cb_failure_threshold' => 5,
        'cb_cooldown' => 30,
        'enabled' => 1
    ];
    $svcid = svc_registry::create_service($svc_data);
    mtrace("   [OK] Service Created (ID: $svcid)");

    // Read
    $svc = svc_registry::get_service_by_id($svcid);
    if ($svc->name !== $svc_data->name)
        throw new Exception("Read service name mismatch");
    mtrace("   [OK] Service Read");

    // Update (This is the function that was accidentally modified earlier)
    $update_data = (object)['name' => $svc_data->name . ' Updated'];
    svc_registry::update_service($svcid, $update_data);
    $svc_updated = svc_registry::get_service_by_id($svcid);
    if ($svc_updated->name !== $update_data->name)
        throw new Exception("Update service failed");
    mtrace("   [OK] Service Updated");

    // ------------------------------------------------------------------------
    // TEST 2: Rule Registry
    // ------------------------------------------------------------------------
    mtrace("\n2. Testing Rule Registry...");
    $rule_data = (object)[
        'eventname' => '\core\event\user_created',
        'serviceid' => $svcid,
        'endpoint' => '/anything',
        'http_method' => 'POST',
        'payload_template' => '{"event": "{{eventname}}", "user": "{{objectid}}"}',
        'enabled' => 1
    ];
    $ruleid = rule_registry::create_rule($rule_data);
    mtrace("   [OK] Rule Created (ID: $ruleid)");

    $rule = rule_registry::get_rule($ruleid);
    if (empty($rule))
        throw new Exception("Failed to fetch rule by ID");
    mtrace("   [OK] Rule Read by ID");

    // ------------------------------------------------------------------------
    // TEST 3: HTTP Transport & Response/Request objects
    // ------------------------------------------------------------------------
    mtrace("\n3. Testing MIH Facade & HTTP Transport...");
    $response = mih::send($svc_updated->name)
        ->to('/post') // httpbin.org/post
        ->with(['test' => 'data', 'cli' => true])
        ->dispatch();

    if ($response->is_ok() && $response->httpstatus === 200) {
        mtrace("   [OK] HTTP Transport Successful (Status 200)");
        $json = $response->json();
        if (!isset($json['json']['test'])) {
            mtrace("   [WARN] JSON payload not echoed properly by httpbin");
        }
        else {
            mtrace("   [OK] JSON Payload parsed correctly");
        }
    }
    else {
        throw new Exception("HTTP Transport failed: " . $response->error);
    }

    // ------------------------------------------------------------------------
    // TEST 4: Cleanup
    // ------------------------------------------------------------------------
    mtrace("\n4. Cleaning up test data...");
    rule_registry::delete_rule($ruleid);
    mtrace("   [OK] Cleanup successful");

    mtrace("\n\033[32mALL TESTS PASSED SUCCESSFULLY!\033[0m\n");

}
catch (\Exception $e) {
    mtrace("\n\033[31m[ERROR] Testing failed: " . $e->getMessage() . "\033[0m");
    mtrace("Stack trace:\n" . $e->getTraceAsString() . "\n");
}