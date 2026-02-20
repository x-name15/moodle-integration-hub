<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * DB/System definition.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for local_integrationhub.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Result
 */
function xmldb_local_integrationhub_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026021803) {
        // Define field http_method to be added to local_integrationhub_rules.
        $table = new xmldb_table('local_integrationhub_rules');
        $field = new xmldb_field('http_method', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'POST', 'endpoint');

        // Conditionally launch add field http_method.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Integration Hub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021803, 'local', 'integrationhub');
    }

    return true;
}
