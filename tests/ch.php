<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_integrationhub\mih;

// Test 1: Verificar que los custom headers se envían
echo "TEST 1: Custom Headers\n";
echo str_repeat('=', 50) . "\n";

$response = mih::request('test-api', '/headers', [], 'GET');

if ($response->is_ok()) {
    $data = $response->json();
    echo "✅ Request exitoso\n";
    echo "Headers recibidos por el servidor:\n";
    print_r($data['headers']);
    
    // Verificar que nuestros custom headers llegaron
    if (isset($data['headers']['X-Test-Header'])) {
        echo "\n✅ Custom header X-Test-Header: " . $data['headers']['X-Test-Header'] . "\n";
    } else {
        echo "\n❌ No se encontró X-Test-Header\n";
    }
} else {
    echo "❌ Error: {$response->error}\n";
}

echo "\n";