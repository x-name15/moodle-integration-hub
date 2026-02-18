# Transport Drivers

MIH supports three transport protocols: HTTP/REST, AMQP (RabbitMQ), and SOAP. Each is implemented as a driver class that implements the `transport\contract` interface.

---

## The Transport Contract

All drivers implement:

```php
namespace local_integrationhub\transport;

interface contract {
    public function execute(
        \stdClass $service,
        string    $endpoint,
        array     $payload,
        string    $method = ''
    ): array;
}
```

The returned array has this structure:

```php
[
    'success'    => bool,
    'body'       => string|null,
    'httpstatus' => int|null,
    'error'      => string|null,
    'latencyms'  => int,
    'attempts'   => int,
]
```

This is wrapped by the Gateway into a `gateway_response` object before returning to the caller.

---

## HTTP / REST Transport

**Class:** `local_integrationhub\transport\http`
**File:** `classes/transport/http.php`
**Uses:** PHP native cURL (`lib/filelib.php` curl wrapper)

### How It Works

1. Constructs the full URL: `{base_url}/{endpoint}`
2. Sets headers: `Content-Type: application/json`, `Accept: application/json`
3. Applies authentication header based on `auth_type`
4. Serializes `$payload` to JSON for the request body (or query string for GET)
5. Executes the cURL request with the configured timeout
6. Determines success based on HTTP status code (2xx = success)

### Authentication

| `auth_type` | Header Sent |
|-------------|-------------|
| `bearer` | `Authorization: Bearer {auth_token}` |
| `apikey` | `X-API-Key: {auth_token}` |
| *(empty)* | No authentication header |

### HTTP Methods

| Method | Payload Location |
|--------|-----------------|
| `GET` | Query string (JSON-encoded values) |
| `POST` | Request body (JSON) |
| `PUT` | Request body (JSON) |
| `PATCH` | Request body (JSON) |
| `DELETE` | Request body (JSON) |

### Success Criteria

A response is considered successful if the HTTP status code is in the `2xx` range (200–299).

### URL Construction

```
base_url = https://api.example.com/v1
endpoint = /users/sync

Full URL = https://api.example.com/v1/users/sync
```

Leading slashes in `endpoint` are handled automatically.

### Example Request

```
POST https://api.example.com/v1/users/sync
Authorization: Bearer eyJhbGci...
Content-Type: application/json
Accept: application/json

{"userid": 5, "action": "created", "timestamp": 1708258939}
```

### Timeout Behavior

If the request exceeds `$service->timeout` seconds, cURL aborts and returns an error. The error is recorded in the log and the circuit breaker registers a failure.

---

## AMQP / RabbitMQ Transport

**Class:** `local_integrationhub\transport\amqp`
**File:** `classes/transport/amqp.php`
**Helper:** `local_integrationhub\transport\amqp_helper`
**Requires:** `php-amqplib/php-amqplib` (Composer)

### How It Works

1. Parses the connection URL from `$service->base_url`
2. Creates a connection via `amqp_helper::create_connection()`
3. Opens a channel
4. Optionally declares a queue (if `queue_declare` is in the URL)
5. Publishes the JSON payload as a persistent AMQP message
6. Closes the channel and connection

### Connection URL Format

```
amqp://user:password@host:port/vhost?exchange=X&routing_key=Y&queue_declare=Z&dlq=DLQ
```

| URL Component | Description | Default |
|---------------|-------------|---------|
| `scheme` | `amqp` (plain) or `amqps` (SSL/TLS) | `amqp` |
| `user` | RabbitMQ username | `guest` |
| `password` | RabbitMQ password | `guest` |
| `host` | Broker hostname or IP | `localhost` |
| `port` | Broker port | `5672` (amqp), `5671` (amqps) |
| `vhost` | Virtual host (URL-encoded) | `/` |
| `exchange` | Exchange name (query param) | *(default exchange)* |
| `routing_key` | Default routing key (query param) | *(empty)* |
| `queue_declare` | Queue to auto-declare (query param) | *(none)* |
| `dlq` | Dead letter queue name (query param) | *(none)* |

### Routing Key Resolution

The routing key is determined in this order:

1. The `endpoint` parameter passed to `gateway->request()` (highest priority)
2. The `routing_key` query parameter in the connection URL
3. The `queue_declare` queue name (fallback for direct-to-queue pattern)

```php
// From amqp.php:
$routingkey = ltrim($endpoint, '/');
if (empty($routingkey) && !empty($query['routing_key'])) {
    $routingkey = $query['routing_key'];
}
// Fallback: direct to declared queue
if (empty($exchange) && empty($routingkey) && !empty($query['queue_declare'])) {
    $routingkey = $query['queue_declare'];
}
```

### Message Properties

Published messages have:
- `delivery_mode = DELIVERY_MODE_PERSISTENT` — messages survive broker restarts
- `content_type = application/json`

### SSL/TLS (AMQPS)

Use `amqps://` in the connection URL for SSL connections (port 5671):

```
amqps://user:password@rabbitmq.example.com:5671/production
```

The `amqp_helper` creates an `AMQPSSLConnection` with `verify_peer = false` by default. For strict production environments, extend `amqp_helper::create_connection()` to pass certificate paths.

### Exchange Patterns

#### Default Exchange (Direct to Queue)

```
amqp://guest:guest@localhost:5672/?queue_declare=my_queue
```

Publishes directly to `my_queue` using the default exchange.

#### Named Exchange with Routing Key

```
amqp://guest:guest@localhost:5672/?exchange=moodle_events&routing_key=events.user
```

Publishes to the `moodle_events` exchange with routing key `events.user`.

#### Topic Exchange

```
amqp://guest:guest@localhost:5672/?exchange=moodle_topic
```

Then override the routing key per-rule:
- Rule for `user_created` → endpoint: `events.user.created`
- Rule for `course_completed` → endpoint: `events.course.completed`

---

## SOAP Transport

**Class:** `local_integrationhub\transport\soap`
**File:** `classes/transport/soap.php`
**Uses:** PHP native `SoapClient`

### How It Works

1. Creates a `SoapClient` with the WSDL URL from `$service->base_url`
2. Calls the SOAP method named by `$endpoint`
3. Passes `$payload` as the method arguments
4. Converts the response to JSON for consistency with other transports

### Service Configuration

| Field | Value |
|-------|-------|
| **Base URL** | The WSDL URL: `https://service.example.com/api?wsdl` |
| **Type** | `SOAP` |

### Calling a SOAP Method

```php
// endpoint = SOAP method name
$response = $gateway->request(
    'legacy-soap-service',
    'GetUserById',           // SOAP method name
    ['UserId' => 42],        // Method parameters
);
```

This translates to:

```php
$client = new SoapClient('https://service.example.com/api?wsdl', $options);
$result = $client->__soapCall('GetUserById', [['UserId' => 42]]);
```

### SoapClient Options

```php
$options = [
    'connection_timeout' => $service->timeout,
    'exceptions'         => true,   // Throw SoapFault on errors
    'trace'              => true,   // Enable request/response tracing
    'cache_wsdl'         => WSDL_CACHE_DISK, // Cache WSDL to disk
];
```

### Error Handling

| Exception | Meaning |
|-----------|---------|
| `SoapFault` | SOAP-level error (e.g., method not found, invalid parameters) |
| `Exception` | Connection error, WSDL parse failure, timeout |

Both are caught and returned as error results.

### Response Handling

The SOAP response object is JSON-encoded for consistency:

```php
$response_json = json_encode($response);
return $this->success_result($response_json, $starttime, $attempts, 200);
```

---

## Adding a Custom Transport

To add a new transport (e.g., gRPC, GraphQL):

1. Create `classes/transport/grpc.php` implementing `contract`:

```php
namespace local_integrationhub\transport;

class grpc implements contract {
    use transport_utils;

    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array {
        $starttime = microtime(true);
        // ... your implementation
        return $this->success_result($body, $starttime, 1, 200);
    }
}
```

2. Register it in `gateway::get_transport_driver()`:

```php
private function get_transport_driver(string $type): transport\contract {
    return match($type) {
        'amqp' => new transport\amqp(),
        'soap' => new transport\soap(),
        'grpc' => new transport\grpc(),  // Add here
        default => new transport\http(),
    };
}
```

3. Add the type option to the service form in `index.php`.
