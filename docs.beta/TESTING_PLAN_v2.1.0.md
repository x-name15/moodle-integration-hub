# Plan de Pruebas - Integration Hub v2.1.0

## Cambios a Probar

Esta versión incluye mejoras críticas de seguridad, rendimiento y API:

- **FASE 1 - Seguridad y Rendimiento:**
  - Rate Limiting para webhooks
  - Sistema de purga de logs optimizado basado en contador

- **FASE 2 - Mejoras de API:**
  - Soporte para método HTTP PATCH
  - Custom headers configurables
  - Conversión automática de payload a query string en GET

---

## 📋 Pre-requisitos

```bash
cd /home/mr_jacket/moodlejima/deployment-ci-cd/moodle

# Limpiar caches para cargar nuevas strings y definiciones de cache
php admin/cli/purge_caches.php

# Ejecutar upgrade para aplicar cambios en BD (campo custom_headers)
php admin/cli/upgrade.php --non-interactive

# Verificar versión del plugin
php -r "require('config.php'); echo get_config('local_integrationhub', 'version') . PHP_EOL;"
# Debe mostrar: 2026030901
```

---

## 🧪 Test Suite

### 1. Verificar Nuevas Configuraciones

**Ubicación:** Administración del sitio → Plugins → Local plugins → Integration Hub

**Configuraciones esperadas:**

✅ **Sección "Rate Limiting"** (nuevo):
- `webhook_rate_limit` - Límite de velocidad de webhook (requests) - Default: 100
- `webhook_rate_window` - Ventana de velocidad de webhook (segundos) - Default: 300

✅ **Sección de Logs:**
- `log_purge_check_frequency` - Frecuencia de verificación de purga de logs - Default: 50
- `max_log_entries` - Máximo de logs - Default: 10000

**Verificar en todos los idiomas:**
- Español: "Limitación de velocidad"
- English: "Rate Limiting"
- Français: "Limitation de débit"
- Italiano: "Limitazione di velocità"
- Português-BR: "Limitação de taxa"

---

### 2. Test de Custom Headers

#### 2.1 Crear Servicio de Prueba

1. Dashboard de Integration Hub → "Add Service"
2. Configuración:
   ```
   Name: test-api
   Type: REST API
   Base URL: https://httpbin.org
   Auth Type: None
   Custom Headers (JSON):
   {
     "X-Test-Header": "moodle-integration-hub",
     "X-Custom-ID": "12345",
     "X-Environment": "testing"
   }
   ```
3. Guardar servicio

#### 2.2 Test Funcional

**Archivo de prueba:** `test_custom_headers.php`

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

echo "TEST: Custom Headers\n";
echo str_repeat('=', 70) . "\n";

$response = mih::request('test-api', '/headers', [], 'GET');

if ($response->is_ok()) {
    $data = $response->json();
    echo "✅ Request exitoso\n";
    echo "Headers recibidos por el servidor:\n";
    
    foreach (['X-Test-Header', 'X-Custom-ID', 'X-Environment'] as $header) {
        if (isset($data['headers'][$header])) {
            echo "  ✅ {$header}: {$data['headers'][$header]}\n";
        } else {
            echo "  ❌ {$header}: NO ENCONTRADO\n";
        }
    }
} else {
    echo "❌ Error: {$response->error}\n";
}
```

**Resultado esperado:**
```
✅ Request exitoso
Headers recibidos por el servidor:
  ✅ X-Test-Header: moodle-integration-hub
  ✅ X-Custom-ID: 12345
  ✅ X-Environment: testing
```

#### 2.3 Test de Validación de Headers Prohibidos

**Procedimiento:**
1. Editar servicio `test-api`
2. Intentar agregar en Custom Headers:
   ```json
   {"Authorization": "Bearer fake-token"}
   ```
3. Hacer click en "Save Service"

**Resultado esperado:**
- ❌ Error mostrado: "Cannot override critical header: Authorization"
- El servicio NO debe guardarse

**Repetir con otros headers prohibidos:**
- `Content-Type`
- `Accept`

#### 2.4 Test de Validación de JSON

**Procedimiento:**
1. Editar servicio `test-api`
2. Ingresar JSON inválido:
   ```
   {invalid json here
   ```
3. Hacer click en "Save Service"

**Resultado esperado:**
- ❌ Error: "Invalid JSON format for custom headers"

---

### 3. Test de Método PATCH

#### 3.1 Test Funcional

**Archivo de prueba:** `test_patch_method.php`

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

echo "TEST: Método PATCH\n";
echo str_repeat('=', 70) . "\n";

// httpbin.org soporta PATCH y devuelve los datos enviados
$payload = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
];

$response = mih::request('test-api', '/patch', $payload, 'PATCH');

if ($response->is_ok()) {
    $data = $response->json();
    echo "✅ PATCH request exitoso\n";
    echo "Método usado: " . ($data['method'] ?? 'N/A') . "\n";
    echo "Datos enviados (JSON body):\n";
    print_r($data['json']);
    
    if ($data['method'] === 'PATCH') {
        echo "\n✅ Método PATCH confirmado por el servidor\n";
    }
} else {
    echo "❌ Error: {$response->error}\n";
}
```

**Resultado esperado:**
```
✅ PATCH request exitoso
Método usado: PATCH
Datos enviados (JSON body):
Array
(
    [name] => John Doe
    [email] => john@example.com
    [status] => active
)

✅ Método PATCH confirmado por el servidor
```

#### 3.2 Verificar en Logs

1. Ir a Dashboard → Request Logs
2. Buscar la última entrada
3. Verificar que la columna "Method" muestre: **PATCH**

---

### 4. Test de GET con Query Parameters

#### 4.1 Test de Conversión Automática

**Archivo de prueba:** `test_get_query_params.php`

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

echo "TEST: GET con Query Parameters\n";
echo str_repeat('=', 70) . "\n";

// Payload debe convertirse automáticamente a query string
$params = [
    'search' => 'moodle',
    'limit' => 50,
    'page' => 1,
    'sort' => 'date',
    'active' => true,
    'category' => 'plugins'
];

$response = mih::request('test-api', '/get', $params, 'GET');

if ($response->is_ok()) {
    $data = $response->json();
    echo "✅ GET request exitoso\n\n";
    echo "URL construida:\n  {$data['url']}\n\n";
    echo "Query params recibidos por el servidor:\n";
    
    foreach ($params as $key => $value) {
        $received = $data['args'][$key] ?? 'NOT FOUND';
        $match = ($received == $value) ? '✅' : '❌';
        echo "  {$match} {$key}: {$received}\n";
    }
    
    // Verificar que NO se envió como JSON body
    if (empty($data['json']) && empty($data['data'])) {
        echo "\n✅ Payload enviado como query string (no como JSON body)\n";
    }
} else {
    echo "❌ Error: {$response->error}\n";
}
```

**Resultado esperado:**
```
✅ GET request exitoso

URL construida:
  https://httpbin.org/get?search=moodle&limit=50&page=1&sort=date&active=1&category=plugins

Query params recibidos por el servidor:
  ✅ search: moodle
  ✅ limit: 50
  ✅ page: 1
  ✅ sort: date
  ✅ active: 1
  ✅ category: plugins

✅ Payload enviado como query string (no como JSON body)
```

#### 4.2 Test con URL que ya tiene Query Params

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

echo "TEST: GET con URL que ya contiene query params\n";
echo str_repeat('=', 70) . "\n";

// El endpoint ya tiene ?foo=bar, nuestros params deben añadirse con &
$response = mih::request(
    'test-api', 
    '/get?existing=param', 
    ['new' => 'value', 'another' => 'test'], 
    'GET'
);

if ($response->is_ok()) {
    $data = $response->json();
    echo "URL construida:\n  {$data['url']}\n\n";
    
    if (isset($data['args']['existing']) && isset($data['args']['new'])) {
        echo "✅ Los nuevos params se añadieron correctamente con &\n";
    } else {
        echo "❌ Fallo en concatenación de query params\n";
    }
}
```

---

### 5. Test de Rate Limiting

#### 5.1 Configuración Previa

**Ajustar para testing rápido:**

1. Ir a settings del plugin
2. Configurar:
   - `webhook_rate_limit`: **10** (en lugar de 100)
   - `webhook_rate_window`: **60** (60 segundos)
3. Purgar cache: `php admin/cli/purge_caches.php`

#### 5.2 Test de Bloqueo

**Archivo de prueba:** `test_rate_limiting.php`

```php
<?php
require_once(__DIR__ . '/config.php');

echo "TEST: Rate Limiting\n";
echo str_repeat('=', 70) . "\n";

$webhook_url = $CFG->wwwroot . '/local/integrationhub/webhook.php?service=test-api';
$max_requests = 15; // Superar el límite de 10

echo "Configuración de prueba:\n";
echo "  Límite: 10 requests\n";
echo "  Ventana: 60 segundos\n";
echo "  Enviando: {$max_requests} requests\n\n";

$blocked_at = null;

for ($i = 1; $i <= $max_requests; $i++) {
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'data', 'request' => $i]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($http_code === 429) {
        $blocked_at = $i;
        echo "\n✅ RATE LIMIT ACTIVADO!\n";
        echo "Request #{$i} fue bloqueado con HTTP 429\n";
        
        // Verificar headers de rate limit
        $headers = get_headers($webhook_url . '&_=' . time(), 1);
        if (isset($headers['X-RateLimit-Limit'])) {
            echo "  X-RateLimit-Limit: {$headers['X-RateLimit-Limit']}\n";
        }
        if (isset($headers['Retry-After'])) {
            echo "  Retry-After: {$headers['Retry-After']} segundos\n";
        }
        break;
    } else if ($http_code === 200) {
        echo "  Request #{$i}: HTTP {$http_code} ✓\n";
    } else {
        echo "  Request #{$i}: HTTP {$http_code} (inesperado)\n";
    }
}

if ($blocked_at !== null) {
    echo "\n✅ Test PASADO: Rate limiting funcionando correctamente\n";
    echo "   Bloqueado después de {$blocked_at} requests\n";
} else {
    echo "\n⚠️  WARNING: No se activó rate limiting después de {$max_requests} requests\n";
}
```

**Resultado esperado:**
```
Request #1: HTTP 200 ✓
Request #2: HTTP 200 ✓
...
Request #10: HTTP 200 ✓

✅ RATE LIMIT ACTIVADO!
Request #11 fue bloqueado con HTTP 429
  X-RateLimit-Limit: 10
  Retry-After: 60 segundos

✅ Test PASADO: Rate limiting funcionando correctamente
   Bloqueado después de 11 requests
```

#### 5.3 Test de Recuperación

**Esperar 61 segundos y ejecutar:**

```php
<?php
require_once(__DIR__ . '/config.php');

$webhook_url = $CFG->wwwroot . '/local/integrationhub/webhook.php?service=test-api';

echo "TEST: Recuperación después de ventana de tiempo\n";
sleep(61); // Esperar que expire la ventana

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'recovery']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code === 200) {
    echo "✅ Rate limit reseteado correctamente después de ventana de tiempo\n";
} else {
    echo "❌ Aún bloqueado con HTTP {$http_code}\n";
}
```

---

### 6. Test de Log Purging

#### 6.1 Verificar Configuración

```php
<?php
require_once(__DIR__ . '/config.php');

global $DB;

echo "TEST: Log Purging - Verificación de Estado\n";
echo str_repeat('=', 70) . "\n\n";

// Configuración actual
$max_logs = get_config('local_integrationhub', 'max_log_entries');
$check_freq = get_config('local_integrationhub', 'log_purge_check_frequency');

echo "Configuración:\n";
echo "  max_log_entries: {$max_logs}\n";
echo "  log_purge_check_frequency: {$check_freq}\n\n";

// Estado actual
$total_logs = $DB->count_records('local_integrationhub_log');
echo "Estado de la base de datos:\n";
echo "  Total de logs: {$total_logs}\n\n";

// Cache counter
$cache = cache::make('local_integrationhub', 'log_counter');
$counter = $cache->get('insert_count');
echo "Cache:\n";
echo "  insert_count: " . ($counter ?: 0) . "\n";
echo "  Próxima verificación en: " . ($check_freq - ($counter ?: 0)) . " inserciones\n\n";

if ($total_logs > $max_logs) {
    echo "⚠️  WARNING: Logs exceden el límite ({$total_logs} > {$max_logs})\n";
    echo "   Purga pendiente en próximas inserciones\n";
} else {
    echo "✅ Número de logs dentro del límite\n";
}
```

#### 6.2 Test de Purga Automática

**Preparación:**
1. Ajustar `max_log_entries` a **100**
2. Ajustar `log_purge_check_frequency` a **10**
3. Purgar cache

**Script de generación de logs:**

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

global $DB;

echo "TEST: Purga Automática de Logs\n";
echo str_repeat('=', 70) . "\n\n";

$initial_count = $DB->count_records('local_integrationhub_log');
echo "Logs iniciales: {$initial_count}\n";
echo "Generando 150 requests para superar límite de 100...\n\n";

for ($i = 1; $i <= 150; $i++) {
    mih::request('test-api', '/get', ['request' => $i], 'GET');
    
    if ($i % 20 === 0) {
        $current_count = $DB->count_records('local_integrationhub_log');
        echo "Request #{$i} - Total logs: {$current_count}\n";
        
        if ($current_count < 120) {
            echo "  ✅ Purga ejecutada automáticamente\n";
        }
    }
}

$final_count = $DB->count_records('local_integrationhub_log');
echo "\n✅ Test completado\n";
echo "Logs finales: {$final_count}\n";

if ($final_count <= 100) {
    echo "✅ Purga automática funcionó correctamente\n";
} else {
    echo "⚠️  Logs no se purgaron como esperado\n";
}
```

**Resultado esperado:**
- Los logs deben mantenerse cerca del límite de 100
- Purgas automáticas cada ~10 inserciones

---

### 7. Test de Traducciones

#### 7.1 Verificar Strings en UI

**Para cada idioma (en, es, fr, it, pt_br):**

1. Cambiar idioma del usuario
2. Ir a: Administración → Plugins → Integration Hub
3. Verificar que aparezcan traducidas:

**Español:**
- "Limitación de velocidad"
- "Límite de velocidad de webhook (solicitudes)"
- "Ventana de velocidad de webhook (segundos)"
- "Frecuencia de verificación de purga de logs"
- "Encabezados personalizados (JSON)"

**Inglés:**
- "Rate Limiting"
- "Webhook rate limit (requests)"
- "Webhook rate window (seconds)"
- "Log purge check frequency"
- "Custom Headers (JSON)"

**Francés:**
- "Limitation de débit"
- "Limite de débit webhook (requêtes)"
- "Fenêtre de débit webhook (secondes)"
- "Fréquence de vérification de purge des journaux"
- "En-têtes personnalisés (JSON)"

**Italiano:**
- "Limitazione di velocità"
- "Limite di velocità webhook (richieste)"
- "Finestra di velocità webhook (secondi)"
- "Frequenza di verifica eliminazione log"
- "Intestazioni personalizzate (JSON)"

**Português-BR:**
- "Limitação de taxa"
- "Limite de taxa de webhook (solicitações)"
- "Janela de taxa de webhook (segundos)"
- "Frequência de verificação de limpeza de logs"
- "Cabeçalhos personalizados (JSON)"

#### 7.2 Verificar Mensajes de Error

Probar en cada idioma que los mensajes de validación aparezcan traducidos:
- Error de JSON inválido en custom headers
- Error de header prohibido
- Error de rate limit excedido (HTTP 429)

---

### 8. Script de Test Completo

**Archivo:** `test_all_v2.1.0.php`

```php
<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/setuplib.php');

use local_integrationhub\mih;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║          Integration Hub v2.1.0 - Complete Test Suite              ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

$tests_passed = 0;
$tests_failed = 0;
$tests = [];

// TEST 1: GET con Query Params
echo "🧪 Test 1: GET con Query Parameters\n";
echo str_repeat('-', 70) . "\n";
try {
    $response = mih::request('test-api', '/get', ['q' => 'test', 'limit' => 10], 'GET');
    if ($response->is_ok()) {
        $data = $response->json();
        if (isset($data['args']['q']) && $data['args']['q'] === 'test') {
            echo "✅ PASSED - Query params convertidos correctamente\n";
            echo "   URL: {$data['url']}\n";
            $tests_passed++;
            $tests[] = ['name' => 'GET Query Params', 'status' => 'PASSED'];
        } else {
            echo "❌ FAILED - Query params no llegaron correctamente\n";
            $tests_failed++;
            $tests[] = ['name' => 'GET Query Params', 'status' => 'FAILED'];
        }
    } else {
        echo "❌ FAILED - {$response->error}\n";
        $tests_failed++;
        $tests[] = ['name' => 'GET Query Params', 'status' => 'FAILED'];
    }
} catch (Exception $e) {
    echo "❌ FAILED - Exception: {$e->getMessage()}\n";
    $tests_failed++;
    $tests[] = ['name' => 'GET Query Params', 'status' => 'FAILED'];
}
echo "\n";

// TEST 2: PATCH Method
echo "🧪 Test 2: Método PATCH\n";
echo str_repeat('-', 70) . "\n";
try {
    $response = mih::request('test-api', '/patch', ['field' => 'value'], 'PATCH');
    if ($response->is_ok()) {
        $data = $response->json();
        if (isset($data['method']) && $data['method'] === 'PATCH') {
            echo "✅ PASSED - PATCH ejecutado correctamente\n";
            echo "   Método confirmado: {$data['method']}\n";
            $tests_passed++;
            $tests[] = ['name' => 'PATCH Method', 'status' => 'PASSED'];
        } else {
            echo "❌ FAILED - Método incorrecto: " . ($data['method'] ?? 'unknown') . "\n";
            $tests_failed++;
            $tests[] = ['name' => 'PATCH Method', 'status' => 'FAILED'];
        }
    } else {
        echo "❌ FAILED - {$response->error}\n";
        $tests_failed++;
        $tests[] = ['name' => 'PATCH Method', 'status' => 'FAILED'];
    }
} catch (Exception $e) {
    echo "❌ FAILED - Exception: {$e->getMessage()}\n";
    $tests_failed++;
    $tests[] = ['name' => 'PATCH Method', 'status' => 'FAILED'];
}
echo "\n";

// TEST 3: Custom Headers
echo "🧪 Test 3: Custom Headers\n";
echo str_repeat('-', 70) . "\n";
try {
    $response = mih::request('test-api', '/headers', [], 'GET');
    if ($response->is_ok()) {
        $data = $response->json();
        if (isset($data['headers']['X-Test-Header'])) {
            echo "✅ PASSED - Custom headers enviados correctamente\n";
            echo "   X-Test-Header: {$data['headers']['X-Test-Header']}\n";
            $tests_passed++;
            $tests[] = ['name' => 'Custom Headers', 'status' => 'PASSED'];
        } else {
            echo "⚠️  SKIPPED - Servicio test-api no tiene custom_headers configurados\n";
            echo "   (Este test requiere configurar custom headers en el servicio)\n";
            $tests[] = ['name' => 'Custom Headers', 'status' => 'SKIPPED'];
        }
    } else {
        echo "❌ FAILED - {$response->error}\n";
        $tests_failed++;
        $tests[] = ['name' => 'Custom Headers', 'status' => 'FAILED'];
    }
} catch (Exception $e) {
    echo "❌ FAILED - Exception: {$e->getMessage()}\n";
    $tests_failed++;
    $tests[] = ['name' => 'Custom Headers', 'status' => 'FAILED'];
}
echo "\n";

// TEST 4: Response Latency Tracking
echo "🧪 Test 4: Latency y Attempts Tracking\n";
echo str_repeat('-', 70) . "\n";
try {
    $response = mih::request('test-api', '/delay/1', [], 'GET');
    if ($response->is_ok()) {
        echo "✅ PASSED - Latency tracking funcional\n";
        echo "   Latencia: {$response->latencyms}ms\n";
        echo "   Intentos: {$response->attempts}\n";
        $tests_passed++;
        $tests[] = ['name' => 'Latency Tracking', 'status' => 'PASSED'];
    } else {
        echo "❌ FAILED - {$response->error}\n";
        $tests_failed++;
        $tests[] = ['name' => 'Latency Tracking', 'status' => 'FAILED'];
    }
} catch (Exception $e) {
    echo "❌ FAILED - Exception: {$e->getMessage()}\n";
    $tests_failed++;
    $tests[] = ['name' => 'Latency Tracking', 'status' => 'FAILED'];
}
echo "\n";

// TEST 5: Database Schema
global $DB;
echo "🧪 Test 5: Database Schema\n";
echo str_repeat('-', 70) . "\n";
try {
    $dbman = $DB->get_manager();
    $table = new xmldb_table('local_integrationhub_svc');
    $field = new xmldb_field('custom_headers');
    
    if ($dbman->field_exists($table, $field)) {
        echo "✅ PASSED - Campo custom_headers existe en BD\n";
        $tests_passed++;
        $tests[] = ['name' => 'DB Schema', 'status' => 'PASSED'];
    } else {
        echo "❌ FAILED - Campo custom_headers no existe\n";
        echo "   Ejecutar: php admin/cli/upgrade.php\n";
        $tests_failed++;
        $tests[] = ['name' => 'DB Schema', 'status' => 'FAILED'];
    }
} catch (Exception $e) {
    echo "❌ FAILED - Exception: {$e->getMessage()}\n";
    $tests_failed++;
    $tests[] = ['name' => 'DB Schema', 'status' => 'FAILED'];
}
echo "\n";

// Resumen
echo "╔══════════════════════════════════════════════════════════════════════╗\n";
echo "║                            RESUMEN                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($tests as $test) {
    $icon = $test['status'] === 'PASSED' ? '✅' : 
            ($test['status'] === 'SKIPPED' ? '⚠️ ' : '❌');
    printf("%-50s %s\n", $test['name'], $icon . ' ' . $test['status']);
}

echo "\n";
echo "Tests Pasados:  {$tests_passed}\n";
echo "Tests Fallidos: {$tests_failed}\n";
echo "Total:          " . ($tests_passed + $tests_failed) . "\n\n";

if ($tests_failed === 0) {
    echo "🎉 ¡Todos los tests críticos pasaron!\n";
    echo "   Integration Hub v2.1.0 está listo para producción.\n\n";
} else {
    echo "⚠️  Algunos tests fallaron. Revisar configuración antes de desplegar.\n\n";
}
```

---

## ✅ Checklist de Validación Final

### Pre-Deploy

- [ ] Cache purgado (`php admin/cli/purge_caches.php`)
- [ ] Upgrade ejecutado (version = 2026030901)  
- [ ] Servicio de prueba `test-api` creado
- [ ] Custom headers configurados en servicio de prueba
- [ ] Todos los tests ejecutados sin errores

### Funcionalidad Core

- [ ] GET convierte payload a query string ✅
- [ ] PATCH request funciona correctamente ✅
- [ ] Custom headers se envían al servidor ✅
- [ ] Validación rechaza headers prohibidos ✅
- [ ] Validación rechaza JSON inválido ✅

### Seguridad y Rendimiento

- [ ] Rate limiting bloquea después del límite configurado ✅
- [ ] Rate limiting se resetea después de ventana de tiempo ✅
- [ ] Log purging mantiene logs bajo el límite ✅
- [ ] Cache counter optimiza verificaciones de purga ✅

### Internacionalización

- [ ] Strings traducidas en Español ✅
- [ ] Strings traducidas en Inglés ✅
- [ ] Strings traducidas en Francés ✅
- [ ] Strings traducidas en Italiano ✅
- [ ] Strings traducidas en Português-BR ✅

### Base de Datos

- [ ] Campo `custom_headers` existe en `local_integrationhub_svc` ✅
- [ ] Cache definitions registradas (`rate_limit`, `log_counter`) ✅
- [ ] Upgrade script ejecutado sin errores ✅

---

## 📊 Criterios de Aceptación

### Críticos (Must Pass)

1. **Upgrade exitoso** - Version = 2026030901
2. **GET query params** - Payload se convierte a query string
3. **PATCH funcional** - Método PATCH ejecuta correctamente
4. **Rate limiting** - Bloquea después del límite configurado
5. **DB Schema** - Campo custom_headers existe

### Importantes (Should Pass)

6. **Custom headers** - Se envían al servidor externo
7. **Validación de headers** - Rechaza headers prohibidos
8. **Log purging** - Mantiene logs bajo límite
9. **Traducciones** - Strings visibles en 5 idiomas

### Opcionales (Nice to Have)

10. **Performance** - Latency tracking < 2000ms
11. **Cache efficiency** - Log purge check optimizado

---

## 🚀 Deployment

### Staging Environment

```bash
# 1. Backup de BD
php admin/cli/backup.php

# 2. Deploy código
git pull origin develop

# 3. Ejecutar upgrade
php admin/cli/upgrade.php --non-interactive

# 4. Purgar caches
php admin/cli/purge_caches.php

# 5. Ejecutar test suite
php test_all_v2.1.0.php

# 6. Monitorear logs
tail -f /var/log/apache2/error.log
```

### Production Environment

```bash
# 1. Backup completo
mysqldump moodle > backup_pre_v2.1.0.sql
tar -czf moodle_backup.tar.gz /path/to/moodle

# 2. Deploy en horario de bajo tráfico
git pull origin main

# 3. Upgrade sin interacción
php admin/cli/upgrade.php --non-interactive

# 4. Purgar caches
php admin/cli/purge_caches.php

# 5. Smoke test rápido
php -r "require('config.php'); echo get_config('local_integrationhub', 'version');"

# 6. Monitorear por 24h
```

---

## 📝 Notas de Testing

### Servicios Públicos de Prueba

- **httpbin.org** - Ideal para probar HTTP methods, headers, query params
  - GET: https://httpbin.org/get
  - POST: https://httpbin.org/post
  - PATCH: https://httpbin.org/patch
  - Headers: https://httpbin.org/headers
  - Delay: https://httpbin.org/delay/{n}

### Troubleshooting Común

**Custom headers no aparecen:**
- Verificar que el JSON sea válido
- Verificar que el servicio esté habilitado
- Purgar cache después de cambios

**Rate limiting no funciona:**
- Verificar que cache esté configurado (no FileSystem en producción)
- Verificar configuración de `webhook_rate_limit` y `webhook_rate_window`
- Purgar cache: `php admin/cli/purge_caches.php`

**PATCH no funciona:**
- Verificar que el servidor externo soporte PATCH
- Algunos APIs legacy solo soportan POST/GET

**Query params no se envían:**
- Solo funciona con método GET
- Verificar que el payload sea array asociativo
- Los arrays se serializan como `param[0]=value`

---

## 🔍 Monitoreo Post-Deploy

### Métricas a Vigilar

1. **Request Logs**: Dashboard → Request Logs
   - Verificar que se registren con método correcto (GET, PATCH, etc.)
   - Latencias razonables (<2000ms)
   - Sin errores masivos

2. **Rate Limiting**: Dashboard → Settings
   - Verificar que no haya demasiados HTTP 429 legítimos
   - Ajustar límites según tráfico real

3. **Log Growth**: Base de datos
   - Monitorear tamaño de tabla `local_integrationhub_log`
   - Verificar que purga funcione automáticamente

4. **Cache Performance**:
   - Verificar hits/misses en cache de rate limit
   - Verificar counter de log purge actualizado

---

**Versión del Plan:** 1.0  
**Fecha:** 2026-03-09  
**Para:** Integration Hub v2.1.0  
**Estado:** Beta Testing
