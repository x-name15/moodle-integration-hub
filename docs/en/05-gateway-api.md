# Gateway API — Developer Reference

This document is the complete reference for using the MIH Gateway from PHP code in other Moodle plugins or custom scripts.

---

## Overview

The `gateway` class is the single entry point for all outbound integrations. It is a **singleton** — you always get the same instance via `gateway::instance()`.

```php
use local_integrationhub\gateway;

$gateway = gateway::instance();
$response = $gateway->request('service-name', '/endpoint', $payload, 'POST');
```

The Gateway handles everything internally:
- Resolving the service configuration from the database
- Checking the circuit breaker
- Selecting the correct transport driver
- Applying retry logic
- Logging the request
- Returning a consistent response object

---

## The `request()` Method

```php
public function request(
    string $servicename,
    string $endpoint = '/',
    array  $payload  = [],
    string $method   = ''
): gateway_response
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$servicename` | `string` | **Yes** | The service slug as registered in the dashboard. Case-sensitive. |
| `$endpoint` | `string` | No | Path appended to the service's base URL. For AMQP: acts as the routing key. For SOAP: the method name. Default: `/` |
| `$payload` | `array` | No | Data to send. Serialized to JSON for HTTP/AMQP, passed as parameters for SOAP. Default: `[]` |
| `$method` | `string` | No | HTTP method: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`. Ignored for AMQP and SOAP. Default: `POST` |

### Return Value

Returns a `\local_integrationhub\gateway_response` object. See the [Response Object](#the-gateway_response-object) section below.

### Exceptions

`request()` throws `\moodle_exception` in these cases:

| Exception key | When |
|---------------|------|
| `service_not_found` | No service with the given name exists in the database |
| `service_disabled` | The service exists but is marked as disabled |
| `circuit_open` | The circuit breaker is OPEN and the cooldown has not expired |

> **Important:** Network errors, timeouts, and HTTP error responses (4xx, 5xx) do **not** throw exceptions — they are returned as a failed `gateway_response` with `is_ok() === false`. Only the three cases above throw.

---

## The `gateway_response` Object

```php
class gateway_response {
    public bool   $success;
    public ?int   $httpstatus;
    public ?string $body;
    public ?string $error;
    public int    $latencyms;
    public int    $attempts;

    public function is_ok(): bool { ... }
    public function json(bool $assoc = true): mixed { ... }
}
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$success` | `bool` | `true` if the request completed successfully (HTTP 2xx, or AMQP publish succeeded) |
| `$httpstatus` | `int\|null` | HTTP response code. `null` for AMQP (no HTTP response). |
| `$body` | `string\|null` | Raw response body as a string. `null` if no body was returned. |
| `$error` | `string\|null` | Human-readable error message. `null` on success. |
| `$latencyms` | `int` | Total time from request start to response, in milliseconds. Includes all retry delays. |
| `$attempts` | `int` | Total number of attempts made (1 = no retries needed). |

### Methods

#### `is_ok(): bool`

Returns `true` if `$success === true`. Shorthand for checking the response status.

```php
if ($response->is_ok()) {
    // Handle success
}
```

#### `json(bool $assoc = true): mixed`

Decodes `$body` as JSON.

- `$assoc = true` (default): returns an associative array
- `$assoc = false`: returns a `stdClass` object

Returns `null` if `$body` is null or not valid JSON.

```php
$data = $response->json();           // array
$obj  = $response->json(false);      // stdClass
```

---

## Usage Examples

### Basic POST Request

```php
$gateway = \local_integrationhub\gateway::instance();

$response = $gateway->request(
    'my-api',
    '/api/v1/events',
    [
        'type'    => 'user.login',
        'user_id' => $USER->id,
        'time'    => time(),
    ],
    'POST'
);

if ($response->is_ok()) {
    $result = $response->json();
    // $result['status'] === 'accepted', etc.
} else {
    debugging("Integration failed: {$response->error} (HTTP {$response->httpstatus})");
}
```

### GET Request with No Payload

```php
$response = $gateway->request('my-api', '/api/v1/health', [], 'GET');

if ($response->is_ok()) {
    $health = $response->json();
    echo $health['status']; // 'ok'
}
```

### PUT Request (Update)

```php
$response = $gateway->request(
    'crm-service',
    '/contacts/' . $userid,
    ['email' => $newemail, 'updated_at' => date('c')],
    'PUT'
);
```

### AMQP Publish

```php
// The endpoint acts as the routing key
$response = $gateway->request(
    'rabbitmq-prod',
    'events.course.completed',
    [
        'user_id'   => $userid,
        'course_id' => $courseid,
        'grade'     => $grade,
        'timestamp' => time(),
    ]
    // Method is ignored for AMQP
);

if ($response->is_ok()) {
    // Message published to exchange with routing key 'events.course.completed'
}
```

### SOAP Call

```php
// The endpoint is the SOAP method name
$response = $gateway->request(
    'legacy-erp',
    'SyncUser',
    [
        'UserId'   => $userid,
        'FullName' => fullname($user),
        'Email'    => $user->email,
    ]
);
```

### Full Error Handling Pattern

```php
function my_plugin_sync_user(int $userid): bool {
    global $DB;

    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $gateway = \local_integrationhub\gateway::instance();

    try {
        $response = $gateway->request(
            'user-sync-api',
            '/users',
            [
                'id'        => $user->id,
                'username'  => $user->username,
                'email'     => $user->email,
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'created'   => $user->timecreated,
            ],
            'POST'
        );

        if ($response->is_ok()) {
            debugging(
                "User {$userid} synced. Latency: {$response->latencyms}ms, Attempts: {$response->attempts}",
                DEBUG_DEVELOPER
            );
            return true;
        }

        // HTTP error (4xx, 5xx) — log but don't crash
        debugging(
            "Sync failed for user {$userid}: HTTP {$response->httpstatus} — {$response->error}",
            DEBUG_DEVELOPER
        );
        return false;

    } catch (\moodle_exception $e) {
        // Service not found, disabled, or circuit open
        // Log but don't interrupt the user flow
        debugging("Integration Hub error: " . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}
```

---

## Checking Latency and Attempts

```php
$response = $gateway->request('my-api', '/slow-endpoint', $data);

echo "Completed in {$response->latencyms}ms";
echo "Took {$response->attempts} attempt(s)";

if ($response->attempts > 1) {
    debugging("Service required retries — check its health", DEBUG_DEVELOPER);
}
```

---

## Accessing the Raw Body

```php
$response = $gateway->request('my-api', '/raw-data');

if ($response->is_ok()) {
    $raw = $response->body;           // string: '{"status":"ok","data":[...]}'
    $decoded = $response->json();     // array: ['status' => 'ok', 'data' => [...]]
    $obj = $response->json(false);    // stdClass: $obj->status === 'ok'
}
```

---

## Using the Gateway in Scheduled Tasks

The Gateway works inside Moodle scheduled and adhoc tasks:

```php
class my_plugin_sync_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return 'Sync users to external CRM';
    }

    public function execute(): void {
        global $DB;

        $gateway = \local_integrationhub\gateway::instance();
        $users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);

        foreach ($users as $user) {
            try {
                $response = $gateway->request(
                    'crm-api',
                    '/sync/users',
                    ['id' => $user->id, 'email' => $user->email],
                    'POST'
                );

                if (!$response->is_ok()) {
                    mtrace("Failed to sync user {$user->id}: {$response->error}");
                }
            } catch (\moodle_exception $e) {
                mtrace("Gateway error for user {$user->id}: " . $e->getMessage());
                // Continue with next user
            }
        }
    }
}
```

---

## Service Name Conventions

By convention, service names should:
- Be lowercase with hyphens: `my-service-name`
- Be descriptive of the external system: `salesforce-crm`, `slack-notifications`, `rabbitmq-prod`
- Not contain spaces or special characters

The name is used as the lookup key in `gateway->request()`, so it must match exactly what is registered in the dashboard.

---

## Thread Safety

The Gateway singleton is safe to use within a single PHP request. Moodle's PHP execution model is single-threaded per request, so there are no concurrency concerns within a single request lifecycle.
