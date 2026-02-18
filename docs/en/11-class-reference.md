# PHP Class Reference

Complete reference for all PHP classes in MIH. Includes method signatures, parameter descriptions, return types, and usage examples.

---

## `\local_integrationhub\gateway`

**File:** `classes/gateway.php`
**Pattern:** Singleton

The main orchestrator. All outbound integrations go through this class.

### Methods

#### `instance(): self`

Returns the singleton instance.

```php
$gateway = \local_integrationhub\gateway::instance();
```

---

#### `request(string $servicename, string $endpoint, array $payload, string $method): gateway_response`

Makes a request to a registered external service.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$servicename` | string | — | Service slug (the `name` field in the dashboard) |
| `$endpoint` | string | `/` | Path appended to base URL. For AMQP: routing key. For SOAP: method name. |
| `$payload` | array | `[]` | Data to send. JSON-encoded for HTTP/AMQP. |
| `$method` | string | `POST` | HTTP method. Ignored for AMQP and SOAP. |

**Returns:** `gateway_response`

**Throws:** `\moodle_exception` with keys:
- `service_not_found` — service slug not in database
- `service_disabled` — service exists but `enabled = 0`
- `circuit_open` — circuit breaker is OPEN and cooldown has not expired

```php
try {
    $response = $gateway->request('my-api', '/users', ['id' => 5], 'POST');
} catch (\moodle_exception $e) {
    // Handle configuration/circuit errors
}
```

---

## `\local_integrationhub\gateway_response`

**File:** `classes/gateway_response.php`
**Pattern:** Immutable Value Object

Wraps the result of every Gateway request.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$success` | `bool` | `true` if the request completed successfully |
| `$httpstatus` | `int\|null` | HTTP response code. `null` for AMQP. |
| `$body` | `string\|null` | Raw response body |
| `$error` | `string\|null` | Error message. `null` on success. |
| `$latencyms` | `int` | Total time in milliseconds (including retries) |
| `$attempts` | `int` | Total attempts made |

### Methods

#### `is_ok(): bool`

Returns `true` if `$success === true`.

```php
if ($response->is_ok()) { ... }
```

---

#### `json(bool $assoc = true): mixed`

Decodes `$body` as JSON.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$assoc` | bool | `true` | `true` = array, `false` = stdClass |

**Returns:** `array`, `stdClass`, scalar, or `null`

```php
$data = $response->json();        // array
$obj  = $response->json(false);   // stdClass
```

---

## `\local_integrationhub\service\registry`

**File:** `classes/service/registry.php`
**Pattern:** Static utility class

Handles all database operations for the `local_integrationhub_svc` table.

### Methods

#### `get_service(string $name): \stdClass|false`

Retrieves a service by its slug name.

```php
$service = registry::get_service('my-api');
if (!$service) {
    // Service not found
}
```

---

#### `get_service_by_id(int $id): \stdClass`

Retrieves a service by its database ID. Throws `\dml_exception` if not found.

```php
$service = registry::get_service_by_id(3);
```

---

#### `get_all_services(): array`

Returns all services as an associative array keyed by `id`.

```php
$services = registry::get_all_services();
foreach ($services as $id => $service) {
    echo $service->name;
}
```

---

#### `create_service(\stdClass $data): int`

Creates a new service record and initializes its circuit breaker state.

**Returns:** The new service's `id`.

```php
$data = new stdClass();
$data->name    = 'new-api';
$data->type    = 'rest';
$data->base_url = 'https://api.example.com';
$data->enabled = 1;
// ... other fields

$id = registry::create_service($data);
```

---

#### `update_service(int $id, \stdClass $data): bool`

Updates an existing service record.

```php
$data = new stdClass();
$data->timeout = 10;
registry::update_service(3, $data);
```

---

#### `delete_service(int $id): bool`

Deletes a service and all associated records (circuit breaker, logs, rules, DLQ entries).

```php
registry::delete_service(3);
```

---

## `\local_integrationhub\service\circuit_breaker`

**File:** `classes/service/circuit_breaker.php`

Manages the circuit breaker state for a service.

### Methods

#### `from_service(\stdClass $service): self`

Factory method. Creates a circuit breaker instance from a service record.

```php
$cb = circuit_breaker::from_service($service);
```

---

#### `is_available(): bool`

Returns `true` if requests should be allowed through.

- CLOSED → `true`
- OPEN + cooldown not expired → `false`
- OPEN + cooldown expired → transitions to HALFOPEN, returns `true`
- HALFOPEN → `true`

```php
if (!$cb->is_available()) {
    throw new \moodle_exception('circuit_open');
}
```

---

#### `record_success(): void`

Records a successful request. Resets `failure_count` to 0. If state was HALFOPEN, transitions to CLOSED.

```php
$cb->record_success();
```

---

#### `record_failure(): void`

Records a failed request. Increments `failure_count`. If `failure_count >= threshold`, transitions to OPEN.

```php
$cb->record_failure();
```

---

#### `get_state(): \stdClass`

Returns the raw circuit breaker DB record.

```php
$state = $cb->get_state();
echo $state->state;         // 'closed', 'open', 'halfopen'
echo $state->failure_count; // int
echo $state->last_failure;  // unix timestamp
```

---

#### `get_state_label(): string`

Returns a human-readable state label: `'CLOSED'`, `'OPEN'`, or `'HALFOPEN'`.

```php
echo $cb->get_state_label(); // 'OPEN'
```

---

#### `reset(): void`

Forces the circuit to CLOSED and resets the failure counter. Used by the dashboard "Reset Circuit" button.

```php
$cb->reset();
```

---

## `\local_integrationhub\service\retry_policy`

**File:** `classes/service/retry_policy.php`

Executes a callable with configurable retry attempts and exponential backoff.

### Methods

#### `from_service(\stdClass $service): self`

Factory method. Creates a retry policy from a service record.

```php
$policy = retry_policy::from_service($service);
```

---

#### `execute(callable $operation): mixed`

Executes `$operation` with retries.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$operation` | `callable` | Function to execute. Receives the attempt number (1-based) as its argument. |

**Returns:** The return value of `$operation` on success.

**Throws:** The last exception if all attempts fail.

```php
$result = $policy->execute(function(int $attempt) use ($transport, $service, $endpoint, $payload, $method) {
    return $transport->execute($service, $endpoint, $payload, $method);
});
```

---

#### `get_total_attempts(): int`

Returns the total number of attempts made in the last `execute()` call.

```php
$policy->execute($fn);
echo $policy->get_total_attempts(); // 3 (if it took 3 attempts)
```

---

## `\local_integrationhub\transport\contract`

**File:** `classes/transport/contract.php`
**Pattern:** Interface

All transport drivers must implement this interface.

### Methods

#### `execute(\stdClass $service, string $endpoint, array $payload, string $method): array`

Executes the transport request.

**Returns:** Array with keys:
- `success` (bool)
- `body` (string|null)
- `httpstatus` (int|null)
- `error` (string|null)
- `latencyms` (int)
- `attempts` (int)

---

## `\local_integrationhub\event\observer`

**File:** `classes/event/observer.php`

### Methods

#### `handle_event(\core\event\base $event): void`

Static method. Called by Moodle's event system for every event. Performs rule lookup, deduplication, and task queuing.

```php
// Registered in db/events.php — not called directly
```

---

## `\local_integrationhub\task\dispatch_event_task`

**File:** `classes/task/dispatch_event_task.php`
**Extends:** `\core\task\adhoc_task`

### Methods

#### `execute(): void`

Processes one queued event. Loads rule and service, interpolates template, calls Gateway, handles DLQ on permanent failure.

---

#### `move_to_dlq(\stdClass $rule, array $payload, string $error): void`

Protected. Writes a failed event to the DLQ table.

---

## `\local_integrationhub\task\consume_responses_task`

**File:** `classes/task/consume_responses_task.php`
**Extends:** `\core\task\scheduled_task`

### Methods

#### `get_name(): string`

Returns `'Consume AMQP Responses'`.

---

#### `execute(): void`

Connects to each AMQP service that has a `response_queue` configured and consumes pending messages.

---

## `\local_integrationhub\transport\amqp_helper`

**File:** `classes/transport/amqp_helper.php`

### Methods

#### `create_connection(string $url, int $timeout = 5): AMQPStreamConnection`

Parses an AMQP URL and creates a connection. Supports both `amqp://` and `amqps://`.

```php
$connection = amqp_helper::create_connection('amqp://guest:guest@localhost:5672/', 5);
```

---

#### `ensure_queue($channel, string $queue): void`

Declares a durable queue if it does not already exist.

```php
amqp_helper::ensure_queue($channel, 'my_queue');
```
