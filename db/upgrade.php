<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for local_integrationhub.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Result
 */
function xmldb_local_integrationhub_upgrade($oldversion)
{
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
