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
 * CLI script to debug service configuration.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Define expected options and their defaults
list($options, $unrecognized) = cli_get_params(
['name' => '', 'help' => false],
['h' => 'help']
);

if ($options['help'] || !$options['name']) {
    echo "Usage: php local/integrationhub/cli/debug_config.php --name='my-service-slug'\n";
    exit(0);
}

$name = $options['name'];

$service = $DB->get_record('local_integrationhub_svc', ['name' => $name]);

if (!$service) {
    cli_error("Service '{$name}' not found.");
}

cli_heading("Service Configuration: {$service->name}");
echo "ID: {$service->id}\n";
echo "Type: {$service->type}\n";
echo "Base URL: {$service->base_url}\n";
echo "Auth Type: {$service->auth_type}\n";

// Parse URL
$parsed = parse_url($service->base_url);
echo "\n--- Parsed URL ---\n";
echo json_encode($parsed, JSON_PRETTY_PRINT);

if (isset($parsed['query'])) {
    parse_str($parsed['query'], $query);
    echo "\n--- Query Params (AMQP Config) ---\n";
    echo json_encode($query, JSON_PRETTY_PRINT);
}

echo "\n";