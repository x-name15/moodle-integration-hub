# Referencia de Clases PHP

Referencia completa de todas las clases PHP en MIH. Incluye firmas de métodos, descripciones de parámetros, tipos de retorno y ejemplos de uso.

---

## `\local_integrationhub\gateway`

**Patrón:** Singleton

El orquestador principal. Todas las integraciones salientes pasan por esta clase.

### `instance(): self`

Devuelve la instancia singleton.

```php
$gateway = \local_integrationhub\gateway::instance();
```

### `request(string $servicename, string $endpoint, array $payload, string $method): gateway_response`

Realiza una petición a un servicio externo registrado.

| Parámetro | Tipo | Por Defecto | Descripción |
|-----------|------|-------------|-------------|
| `$servicename` | string | — | Slug del servicio (campo `name` en el dashboard) |
| `$endpoint` | string | `/` | Ruta añadida a la URL base. Para AMQP: routing key. Para SOAP: nombre del método. |
| `$payload` | array | `[]` | Datos a enviar. JSON-encoded para HTTP/AMQP. |
| `$method` | string | `POST` | Método HTTP. Ignorado para AMQP y SOAP. |

**Lanza:** `\moodle_exception` con claves: `service_not_found`, `service_disabled`, `circuit_open`

---

## `\local_integrationhub\gateway_response`

**Patrón:** Value Object Inmutable

### Propiedades

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$success` | `bool` | `true` si la petición se completó exitosamente |
| `$httpstatus` | `int\|null` | Código de respuesta HTTP. `null` para AMQP. |
| `$body` | `string\|null` | Cuerpo de respuesta crudo |
| `$error` | `string\|null` | Mensaje de error. `null` en éxito. |
| `$latencyms` | `int` | Tiempo total en milisegundos (incluyendo reintentos) |
| `$attempts` | `int` | Total de intentos realizados |

### `is_ok(): bool`

Devuelve `true` si `$success === true`.

### `json(bool $assoc = true): mixed`

Decodifica `$body` como JSON. `true` = array, `false` = stdClass.

---

## `\local_integrationhub\service\registry`

**Patrón:** Clase utilitaria estática

### Métodos Principales

| Método | Retorno | Descripción |
|--------|---------|-------------|
| `get_service(string $name)` | `\stdClass\|false` | Obtiene servicio por slug |
| `get_service_by_id(int $id)` | `\stdClass` | Obtiene servicio por ID (lanza si no existe) |
| `get_all_services()` | `array` | Todos los servicios, indexados por ID |
| `create_service(\stdClass $data)` | `int` | Crea servicio y su circuit breaker. Devuelve ID. |
| `update_service(int $id, \stdClass $data)` | `bool` | Actualiza un servicio existente |
| `delete_service(int $id)` | `bool` | Elimina servicio y todos sus registros asociados |

---

## `\local_integrationhub\service\circuit_breaker`

### Métodos Principales

| Método | Retorno | Descripción |
|--------|---------|-------------|
| `from_service(\stdClass $service)` | `self` | Factory. Crea instancia desde registro de servicio. |
| `is_available()` | `bool` | `true` si las peticiones deben pasar |
| `record_success()` | `void` | Registra éxito. Resetea contador. Cierra circuito si era HALFOPEN. |
| `record_failure()` | `void` | Registra fallo. Incrementa contador. Abre circuito si alcanza umbral. |
| `get_state()` | `\stdClass` | Devuelve el registro DB crudo del circuit breaker |
| `get_state_label()` | `string` | Etiqueta legible: `'CLOSED'`, `'OPEN'`, o `'HALFOPEN'` |
| `reset()` | `void` | Fuerza el circuito a CLOSED y resetea el contador |

---

## `\local_integrationhub\service\retry_policy`

### Métodos Principales

| Método | Retorno | Descripción |
|--------|---------|-------------|
| `from_service(\stdClass $service)` | `self` | Factory. Crea política desde registro de servicio. |
| `execute(callable $operation)` | `mixed` | Ejecuta `$operation` con reintentos. El callable recibe el número de intento (base 1). |
| `get_total_attempts()` | `int` | Total de intentos en la última llamada a `execute()` |

---

## `\local_integrationhub\transport\contract`

**Patrón:** Interfaz

Todos los drivers de transporte deben implementar:

```php
public function execute(
    \stdClass $service,
    string    $endpoint,
    array     $payload,
    string    $method = ''
): array;
```

**Retorna:** Array con claves: `success`, `body`, `httpstatus`, `error`, `latencyms`, `attempts`

---

## `\local_integrationhub\event\observer`

### `handle_event(\core\event\base $event): void`

Método estático. Llamado por el sistema de eventos de Moodle para cada evento. Realiza búsqueda de reglas, deduplicación y encolado de tareas adhoc.

```php
// Registrado en db/events.php — no se llama directamente
```

---

## `\local_integrationhub\task\dispatch_event_task`

**Extiende:** `\core\task\adhoc_task`

### `execute(): void`

Procesa un evento encolado. Carga la regla, interpola el template, llama al Gateway, maneja DLQ en fallo permanente.

---

## `\local_integrationhub\task\consume_responses_task`

**Extiende:** `\core\task\scheduled_task`

### `execute(): void`

Se conecta a cada servicio AMQP que tiene una `response_queue` configurada y consume los mensajes pendientes.

---

## `\local_integrationhub\transport\amqp_helper`

### `create_connection(string $url, int $timeout = 5): AMQPStreamConnection`

Parsea una URL AMQP y crea una conexión. Soporta tanto `amqp://` como `amqps://`.

### `ensure_queue($channel, string $queue): void`

Declara una cola durable si no existe ya.
