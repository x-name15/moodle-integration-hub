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

namespace local_integrationhub\firewall;

defined('MOODLE_INTERNAL') || die();

/**
 * IP Whitelist Guard Tests.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ip_whitelist_test extends \advanced_testcase {

    public function test_empty_whitelist_allows_all() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => ''];
        
        // Should not throw exception
        $guard->inspect($service);
        $this->assertTrue(true);
    }

    public function test_single_ip_match() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => '192.168.1.5'];
        
        // Mock REMOTE_ADDR
        $_SERVER['REMOTE_ADDR'] = '192.168.1.5';
        $guard->inspect($service);
        $this->assertTrue(true);
    }

    public function test_single_ip_mismatch_throws_exception() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => '192.168.1.5'];
        
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Access denied for IP: 10.0.0.1');
        $guard->inspect($service);
    }

    public function test_cidr_match() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => '10.0.0.0/24'];
        
        $_SERVER['REMOTE_ADDR'] = '10.0.0.50';
        $guard->inspect($service);
        $this->assertTrue(true);

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $guard->inspect($service);
        $this->assertTrue(true);
    }

    public function test_cidr_mismatch() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => '10.0.0.0/24'];
        
        $_SERVER['REMOTE_ADDR'] = '10.0.1.50'; // Outside /24
        
        $this->expectException(\moodle_exception::class);
        $guard->inspect($service);
    }

    public function test_multiple_ips() {
        $guard = new ip_whitelist();
        $service = (object)['ip_whitelist' => '192.168.1.1, 10.0.0.0/8, 8.8.8.8'];
        
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';
        $guard->inspect($service);
        
        $_SERVER['REMOTE_ADDR'] = '10.5.5.5';
        $guard->inspect($service);
        
        $this->assertTrue(true);
    }
}
