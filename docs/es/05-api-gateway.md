# API Gateway — Referencia para Desarrolladores

Este documento es la referencia completa para usar el Gateway de MIH desde código PHP en otros plugins de Moodle o scripts personalizados.

---

## Visión General

La clase `gateway` es el único punto de entrada para todas las integraciones salientes. Es un **singleton** — siempre obtienes la misma instancia via `gateway::instance()`.

```php
use local_integrationhub\gateway;

$gateway = gateway::instance();
$response = $gateway->request('nombre-servicio', '/endpoint', $payload, 'POST');
```

El Gateway maneja todo internamente:
- Resolver la configuración del servicio desde la base de datos
- Verificar el circuit breaker
- Seleccionar el driver de transporte correcto
- Aplicar lógica de reintentos
- Registrar la petición en el log
- Devolver un objeto de respuesta consistente

---

## El Método `request()`

```php
public function request(
    string $servicename,
    string $endpoint = '/',
    array  $payload  = [],
    string $method   = ''
): gateway_response
```

### Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `$servicename` | `string` | **Sí** | El slug del servicio tal como está registrado en el dashboard. Sensible a mayúsculas. |
| `$endpoint` | `string` | No | Ruta añadida a la URL base del servicio. Para AMQP: actúa como routing key. Para SOAP: nombre del método. Por defecto: `/` |
| `$payload` | `array` | No | Datos a enviar. Serializado a JSON para HTTP/AMQP, pasado como parámetros para SOAP. Por defecto: `[]` |
| `$method` | `string` | No | Método HTTP: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`. Ignorado para AMQP y SOAP. Por defecto: `POST` |

### Valor de Retorno

Devuelve un objeto `\local_integrationhub\gateway_response`.

### Excepciones

`request()` lanza `\moodle_exception` en estos casos:

| Clave de excepción | Cuándo |
|-------------------|--------|
| `service_not_found` | No existe ningún servicio con ese nombre en la base de datos |
| `service_disabled` | El servicio existe pero está marcado como desactivado |
| `circuit_open` | El circuit breaker está OPEN y el cooldown no ha expirado |

> **Importante:** Los errores de red, timeouts y respuestas HTTP de error (4xx, 5xx) **no** lanzan excepciones — se devuelven como un `gateway_response` fallido con `is_ok() === false`. Solo los tres casos anteriores lanzan excepciones.

---

## El Objeto `gateway_response`

### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$success` | `bool` | `true` si la petición se completó exitosamente (HTTP 2xx, o publicación AMQP exitosa) |
| `$httpstatus` | `int\|null` | Código de respuesta HTTP. `null` para AMQP. |
| `$body` | `string\|null` | Cuerpo de respuesta crudo como string. `null` si no hubo cuerpo. |
| `$error` | `string\|null` | Mensaje de error legible. `null` en éxito. |
| `$latencyms` | `int` | Tiempo total desde inicio de petición hasta respuesta, en milisegundos. Incluye todos los delays de reintento. |
| `$attempts` | `int` | Número total de intentos realizados (1 = sin reintentos necesarios). |

### Métodos

#### `is_ok(): bool`

Devuelve `true` si `$success === true`.

#### `json(bool $assoc = true): mixed`

Decodifica `$body` como JSON. `$assoc = true` (por defecto): devuelve array asociativo. `$assoc = false`: devuelve `stdClass`.

---

## Ejemplos de Uso

### Petición POST Básica

```php
$gateway = \local_integrationhub\gateway::instance();

$response = $gateway->request(
    'mi-api',
    '/api/v1/eventos',
    [
        'tipo'     => 'usuario.login',
        'user_id'  => $USER->id,
        'tiempo'   => time(),
    ],
    'POST'
);

if ($response->is_ok()) {
    $resultado = $response->json();
} else {
    debugging("Integración fallida: {$response->error} (HTTP {$response->httpstatus})");
}
```

### Publicar en AMQP

```php
// El endpoint actúa como routing key
$response = $gateway->request(
    'rabbitmq-prod',
    'events.course.completed',
    [
        'user_id'   => $userid,
        'course_id' => $courseid,
        'timestamp' => time(),
    ]
    // El método se ignora para AMQP
);
```

### Llamada SOAP

```php
// El endpoint es el nombre del método SOAP
$response = $gateway->request(
    'erp-legado',
    'SincronizarUsuario',
    [
        'UserId'   => $userid,
        'NombreCompleto' => fullname($user),
        'Email'    => $user->email,
    ]
);
```

### Patrón Completo de Manejo de Errores

```php
function mi_plugin_sincronizar_usuario(int $userid): bool {
    global $DB;

    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $gateway = \local_integrationhub\gateway::instance();

    try {
        $response = $gateway->request(
            'api-sincronizacion',
            '/usuarios',
            [
                'id'        => $user->id,
                'username'  => $user->username,
                'email'     => $user->email,
            ],
            'POST'
        );

        if ($response->is_ok()) {
            debugging("Usuario {$userid} sincronizado. Latencia: {$response->latencyms}ms");
            return true;
        }

        debugging("Fallo de sincronización: HTTP {$response->httpstatus} — {$response->error}");
        return false;

    } catch (\moodle_exception $e) {
        // Servicio no encontrado, desactivado o circuito abierto
        debugging("Error de Integration Hub: " . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}
```

---

## Uso del Gateway en Tareas Programadas

```php
class mi_plugin_tarea_sync extends \core\task\scheduled_task {

    public function get_name(): string {
        return 'Sincronizar usuarios con CRM externo';
    }

    public function execute(): void {
        global $DB;

        $gateway = \local_integrationhub\gateway::instance();
        $usuarios = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);

        foreach ($usuarios as $usuario) {
            try {
                $response = $gateway->request(
                    'crm-api',
                    '/sync/usuarios',
                    ['id' => $usuario->id, 'email' => $usuario->email],
                    'POST'
                );

                if (!$response->is_ok()) {
                    mtrace("Fallo al sincronizar usuario {$usuario->id}: {$response->error}");
                }
            } catch (\moodle_exception $e) {
                mtrace("Error Gateway para usuario {$usuario->id}: " . $e->getMessage());
                // Continuar con el siguiente usuario
            }
        }
    }
}
```
