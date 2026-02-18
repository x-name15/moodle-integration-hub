<?php
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
print_r($parsed);

if (isset($parsed['query'])) {
    parse_str($parsed['query'], $query);
    echo "\n--- Query Params (AMQP Config) ---\n";
    print_r($query);
}

echo "\n";