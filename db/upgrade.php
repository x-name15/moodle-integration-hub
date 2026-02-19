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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_integrationhub plugin
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_integrationhub_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026021900) {

        // Define table local_integrationhub_svc to be modified.
        $table = new xmldb_table('local_integrationhub_svc');

        // Add firewall fields.
        $fields = [
            new xmldb_field('ip_whitelist', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response_queue'),
            new xmldb_field('hmac_secret', XMLDB_TYPE_TEXT, null, null, null, null, null, 'ip_whitelist'),
            new xmldb_field('hmac_algo', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'sha256', 'hmac_secret'),
            new xmldb_field('hmac_header', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'X-Hub-Signature-256', 'hmac_algo'),
            new xmldb_field('rate_limit_requests', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'hmac_header'),
            new xmldb_field('rate_limit_window', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '60', 'rate_limit_requests'),
            new xmldb_field('payload_schema', XMLDB_TYPE_TEXT, null, null, null, null, null, 'rate_limit_window'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Define table local_integrationhub_nonce to be created.
        $tableNonce = new xmldb_table('local_integrationhub_nonce');

        // Adding fields to table local_integrationhub_nonce.
        $tableNonce->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tableNonce->add_field('serviceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tableNonce->add_field('nonce', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $tableNonce->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tableNonce->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_integrationhub_nonce.
        $tableNonce->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $tableNonce->add_key('fk_serviceid_nonce', XMLDB_KEY_FOREIGN, ['serviceid'], 'local_integrationhub_svc', ['id']);

        // Adding indexes to table local_integrationhub_nonce.
        $tableNonce->add_index('ix_nonce_service', XMLDB_INDEX_UNIQUE, ['serviceid', 'nonce']);
        $tableNonce->add_index('ix_timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_integrationhub_nonce.
        if (!$dbman->table_exists($tableNonce)) {
            $dbman->create_table($tableNonce);
        }

        // Local_integrationhub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021900, 'local', 'integrationhub');
    }

    return true;
}