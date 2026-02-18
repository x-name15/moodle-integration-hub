# Moodle Integration Hub (MIH) — Technical Reference

> **Version:** 2.0 · **Date:** 2026-02-18 · **License:** GPL v3

---

## Table of Contents

1. [Overview](#1-overview)
2. [System Architecture](#2-system-architecture)
3. [Installation and Configuration](#3-installation-and-configuration)
4. [Administrator Guide](#4-administrator-guide)
5. [Internal API — Usage from Plugins](#5-internal-api--usage-from-plugins)
6. [Event Bridge — Automatic Dispatch](#6-event-bridge--automatic-dispatch)
7. [Complete Data Flow](#7-complete-data-flow)
8. [Resilience: Circuit Breaker and Retry](#8-resilience-circuit-breaker-and-retry)
9. [Supported Transports](#9-supported-transports)
10. [Database Schema](#10-database-schema)
11. [PHP Class Reference](#11-php-class-reference)
12. [Internal AJAX Endpoint](#12-internal-ajax-endpoint)
13. [Roles and Permissions](#13-roles-and-permissions)
14. [Scheduled Tasks](#14-scheduled-tasks)
15. [File Structure](#15-file-structure)

---

## 1. Overview

**Moodle Integration Hub (MIH)** is a local Moodle plugin that acts as a **centralized integration layer** between Moodle and any external service — REST APIs, RabbitMQ brokers, or SOAP web services.

### Problem it solves

Without MIH, every plugin that needs to communicate with an external service must:
- Implement its own HTTP logic (curl, tokens, headers)
- Manage retries and timeouts independently
- Handle errors without central visibility
- Duplicate authentication code

**MIH centralizes all of this** into a single configuration and execution point.

### Core capabilities

| Capability | Description |
|------------|-------------|
| **Service Gateway** | Reusable PHP API for HTTP/AMQP calls from any plugin |
| **Event Bridge** | Automatically react to Moodle events without writing code |
| **Circuit Breaker** | Prevents cascading failures by short-circuiting calls to downed services |
| **Retry with Backoff** | Automatic retries with exponential delay |
| **Dead Letter Queue** | Stores failed events for review and replay |
| **Dashboard** | Real-time monitoring: circuit states, latency, error rates |
| **Multi-transport** | REST, AMQP (RabbitMQ), and SOAP support |

---

## 2. System Architecture

```
+------------------------------------------------------------------+
|                          MOODLE CORE                             |
|                                                                  |
|  [Moodle Event] --> [Universal Observer] --> [Adhoc Task Queue]  |
|                                                                  |
|  [External Plugin] --> [Gateway API]                             |
+-----------------------------+------------------------------------+
                              |
                     +--------v--------+
                     |   Gateway.php   |
                     |  (Orchestrator) |
                     +--------+--------+
                              |
             +----------------+----------------+
             |                |                |
    +--------v---+   +--------v---+   +--------v---+
    | Transport  |   | Transport  |   | Transport  |
    |    HTTP    |   |    AMQP    |   |    SOAP    |
    +--------+---+   +--------+---+   +--------+---+
             |                |                |
    +--------v----------------v----------------v---+
    |              External Services               |
    |  REST API  |  RabbitMQ  |  SOAP WebService   |
    +----------------------------------------------+
```

### Key components

| Component | File | Responsibility |
|-----------|------|----------------|
| `gateway` | `classes/gateway.php` | Main orchestrator. Public API entry point for plugins |
| `gateway_response` | `classes/gateway_response.php` | Immutable response value object |
| `service\registry` | `classes/service/registry.php` | Service CRUD operations |
| `service\circuit_breaker` | `classes/service/circuit_breaker.php` | CLOSED/OPEN/HALFOPEN state management |
| `service\retry_policy` | `classes/service/retry_policy.php` | Retry logic with exponential backoff |
| `transport\http` | `classes/transport/http.php` | REST/HTTP driver via cURL |
| `transport\amqp` | `classes/transport/amqp.php` | RabbitMQ driver via php-amqplib |
| `transport\soap` | `classes/transport/soap.php` | SOAP driver |
| `event\observer` | `classes/event/observer.php` | Universal Moodle event listener |
| `task\dispatch_event_task` | `classes/task/dispatch_event_task.php` | Adhoc task for event dispatching |

---

## 3. Installation and Configuration

### Requirements

- Moodle 4.1+
- PHP 8.0+
- For AMQP: `php-amqplib/php-amqplib` (via Composer)

### Installation steps

```bash
# 1. Copy the plugin
cp -r integrationhub /path/to/moodle/local/

# 2. Install PHP dependencies (only if using AMQP)
cd /path/to/moodle/local/integrationhub
composer require php-amqplib/php-amqplib

# 3. Run Moodle upgrade
php admin/cli/upgrade.php

# 4. Verify tables were created:
# local_integrationhub_svc, _cb, _log, _rules, _dlq
```

### Admin settings

Go to **Site Administration > Server > Integration Hub** to configure:
- `max_log_entries` — Maximum log entries before auto-purge (default: 500)

---

## 4. Administrator Guide

### 4.1 Adding a REST Service

1. Go to `/local/integrationhub/index.php`
2. Click **"Add Service"**
3. Fill in the form:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Unique service slug (no spaces) | `my-external-api` |
| **Type** | `REST`, `AMQP`, or `SOAP` | `REST` |
| **Base URL** | Root URL of the service | `https://api.example.com` |
| **Auth Type** | `Bearer` or `API Key` | `Bearer` |
| **Token** | Authentication token | `eyJhbGci...` |
| **Timeout** | Seconds before cancelling | `5` |
| **Max Retries** | Attempts before failing | `3` |
| **Initial Backoff** | Seconds between retries (doubles each time) | `1` |
| **CB Failure Threshold** | Failures to open the circuit | `5` |
| **CB Cooldown** | Seconds before attempting recovery | `30` |

### 4.2 Adding an AMQP Service (RabbitMQ)

When selecting type **AMQP**, a **Connection Builder** appears:

| Field | Description | Default |
|-------|-------------|---------|
| **Host** | Broker hostname | `localhost` |
| **Port** | `5672` (AMQP) or `5671` (AMQPS/SSL) | `5672` |
| **User** | RabbitMQ username | `guest` |
| **Password** | RabbitMQ password | `guest` |
| **VHost** | Virtual host | `/` |
| **Exchange** | Target exchange (optional) | *(empty = default)* |
| **Routing Key** | Default routing key | `my.routing.key` |
| **Queue Declare** | Queue to auto-declare | `my_queue` |
| **DLQ** | Dead Letter Queue name | `my_dlq` |

The AMQP connection URL is built automatically:
```
amqp://user:password@host:5672/vhost?exchange=X&routing_key=Y
```

### 4.3 Adding a Rule (Event Bridge)

1. Go to `/local/integrationhub/rules.php`
2. Click **"Add Rule"**
3. Fill in:

| Field | Description | Example |
|-------|-------------|---------|
| **Event** | Full class name of the Moodle event | `\core\event\user_created` |
| **Service** | Target service (must be active) | `my-external-api` |
| **HTTP Method** | `POST`, `GET`, `PUT`, `PATCH`, `DELETE` | `POST` |
| **Endpoint** | Additional path (optional) | `/webhooks/users` |
| **Template** | JSON with `{{variable}}` placeholders | See section 6 |
| **Active** | Enable/disable the rule | checked |

### 4.4 Monitoring the Dashboard

The dashboard at `/local/integrationhub/index.php` shows:
- **Status Chart** — Global success/failure distribution
- **Latency Chart** — Trend of the last 200 requests
- **Services Table** — Circuit state, average latency, recent errors (last 24h)
- **Action Buttons** — Reset individual or all circuits

---

## 5. Internal API — Usage from Plugins

This is how **other Moodle plugins** use MIH to call external services.

### 5.1 Basic usage

```php
// Get the Gateway instance (Singleton)
$gateway = \local_integrationhub\gateway::instance();

// Make a request
$response = $gateway->request(
    'service-name',         // Service slug as registered in the dashboard
    '/api/v1/endpoint',     // Endpoint path (appended to base URL)
    ['key' => 'value'],     // Payload (PHP array, serialized to JSON)
    'POST'                  // HTTP method (GET, POST, PUT, PATCH, DELETE)
);
```

### 5.2 Parameters of `request()`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$servicename` | `string` | Yes | Service slug (the "Name" field in the dashboard) |
| `$endpoint` | `string` | No | Additional path. Default: `/` |
| `$payload` | `array` | No | Data to send. Default: `[]` |
| `$method` | `string` | No | HTTP method. Default: `POST` |

**Returns:** `\local_integrationhub\gateway_response`

### 5.3 The `gateway_response` object

```php
$response = $gateway->request('my-service', '/execute', $data);

// Check success
if ($response->is_ok()) {
    $body = $response->body;          // string — raw response body
    $data = $response->json();        // mixed — body decoded as array
    $data = $response->json(false);   // object — decoded as stdClass
} else {
    echo $response->error;            // string — error message
    echo $response->httpstatus;       // int|null — HTTP code (e.g. 404, 500)
}

// Metrics
echo $response->latencyms;   // int — latency in milliseconds
echo $response->attempts;    // int — number of attempts made
```

#### `gateway_response` properties

| Property | Type | Description |
|----------|------|-------------|
| `$success` | `bool` | `true` if the request succeeded (HTTP 2xx) |
| `$httpstatus` | `int\|null` | HTTP response code |
| `$body` | `string\|null` | Raw response body |
| `$error` | `string\|null` | Error message if failed |
| `$latencyms` | `int` | Response time in milliseconds |
| `$attempts` | `int` | Number of attempts made (including retries) |

#### `gateway_response` methods

| Method | Returns | Description |
|--------|---------|-------------|
| `is_ok()` | `bool` | `true` if the request succeeded |
| `json(bool $assoc = true)` | `mixed` | Decodes the body as JSON |

### 5.4 Error handling

```php
try {
    $response = $gateway->request('my-service', '/endpoint', $payload);

    if (!$response->is_ok()) {
        // Service responded with an error (4xx, 5xx)
        \core\notification::warning('Service error: ' . $response->error);
        return;
    }

    $result = $response->json();
    // ... process $result

} catch (\moodle_exception $e) {
    // Possible exceptions:
    // - 'service_not_found': Slug does not exist in the dashboard
    // - 'service_disabled': Service is disabled
    // - 'circuit_open': Circuit breaker is open (service is down)
    \core\notification::error('Integration error: ' . $e->getMessage());
}
```

### 5.5 Full example — External plugin

```php
// In any PHP file of an external plugin:
require_once($CFG->dirroot . '/local/integrationhub/classes/gateway.php');

function my_plugin_notify_user(int $userid, string $action): void {
    $gateway = \local_integrationhub\gateway::instance();

    try {
        $response = $gateway->request(
            'notification-system',
            '/api/notifications',
            [
                'userid'  => $userid,
                'action'  => $action,
                'source'  => 'moodle',
                'time'    => time(),
            ],
            'POST'
        );

        if ($response->is_ok()) {
            debugging("Notification sent. Latency: {$response->latencyms}ms");
        } else {
            debugging("Failed to notify: {$response->error}");
        }

    } catch (\moodle_exception $e) {
        // Log but do not interrupt the user flow
        debugging("Integration Hub error: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
}
```

### 5.6 AMQP call

For AMQP-type services, `$endpoint` acts as the **routing key** (overrides the service default):

```php
$response = $gateway->request(
    'rabbitmq-production',
    'events.user.created',  // Specific routing key for this message
    [
        'userid'    => $USER->id,
        'eventname' => 'user_created',
        'timestamp' => time(),
    ]
    // HTTP method is ignored for AMQP
);
```

---

## 6. Event Bridge — Automatic Dispatch

The Event Bridge lets you react to **any Moodle event** without writing PHP code.

### 6.1 How it works

```
Moodle user action
        |
        v
Moodle fires event (e.g. \core\event\user_created)
        |
        v
event\observer::handle_event($event)
        |
        +-- Query: SELECT rules WHERE eventname = ? AND enabled = 1
        |
        +-- No rules? --> return (no action)
        |
        +-- Compute SHA1 signature (eventname + objectid + userid + crud)
        |
        +-- Cache hit? --> return (duplicate event, skip)
        |
        +-- For each rule:
        |       queue_adhoc_task(dispatch_event_task, {ruleid, eventdata})
        |
        +-- cache->set(signature, 1)

[Moodle cron executes adhoc tasks]
        |
        v
task\dispatch_event_task::execute()
        |
        +-- Load rule and service from DB
        +-- Interpolate template with event data
        +-- gateway::instance()->request(service, endpoint, payload, method)
        +-- On failure --> INSERT local_integrationhub_dlq
```

### 6.2 Payload Templates

Templates use `{{variable}}` syntax to interpolate event data:

```json
{
  "event": "{{eventname}}",
  "user_id": {{userid}},
  "object_id": {{objectid}},
  "course_id": {{courseid}},
  "context_id": {{contextid}},
  "timestamp": {{timecreated}},
  "ip": "{{ip}}"
}
```

#### Available template variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{eventname}}` | Full event class name | `\core\event\user_created` |
| `{{userid}}` | ID of the user who triggered the event | `5` |
| `{{objectid}}` | ID of the affected object | `123` |
| `{{courseid}}` | Course ID (if applicable) | `10` |
| `{{contextid}}` | Context ID | `1` |
| `{{timecreated}}` | Unix timestamp of the event | `1708258939` |
| `{{ip}}` | User IP address | `192.168.1.1` |

> **Note:** Use the **"Preview Payload"** button in the rule form to preview the interpolated result with mock data.

### 6.3 Deduplication

The observer includes automatic deduplication via Moodle's cache layer. If the same event (same combination of `eventname + objectid + userid + crud`) fires multiple times in a short period, only the first occurrence is processed.

### 6.4 Common Moodle events

| Event | Description |
|-------|-------------|
| `\core\event\user_created` | User created |
| `\core\event\user_updated` | User updated |
| `\core\event\user_deleted` | User deleted |
| `\core\event\course_created` | Course created |
| `\core\event\course_completed` | Course completed |
| `\core\event\user_enrolment_created` | User enrolled |
| `\core\event\user_enrolment_deleted` | User unenrolled |
| `\core\event\grade_item_updated` | Grade updated |
| `\core\event\user_loggedin` | User logged in |
| `\core\event\user_loggedout` | User logged out |

---

## 7. Complete Data Flow

### 7.1 Gateway flow (direct call from plugin)

```
Plugin PHP
    |
    v
gateway::instance()->request('service', '/endpoint', $payload, 'POST')
    |
    +-- service\registry::get_service('service')
    |       --> DB: local_integrationhub_svc WHERE name = 'service'
    |
    +-- circuit_breaker::from_service($service)->is_available()
    |       OPEN + cooldown not expired --> throw moodle_exception('circuit_open')
    |       OPEN + cooldown expired     --> transition to HALFOPEN --> continue
    |       CLOSED / HALFOPEN           --> continue
    |
    +-- get_transport_driver($service->type)
    |       'rest' --> transport\http
    |       'amqp' --> transport\amqp
    |       'soap' --> transport\soap
    |
    +-- retry_policy::from_service($service)->execute(fn)
    |       Attempt 1: transport->execute($service, $endpoint, $payload, $method)
    |                  --> cURL --> External Service --> HTTP Response
    |       On failure: sleep backoff * 2^attempt seconds
    |       Attempt 2, 3... (up to max_retries)
    |       Returns result of last attempt
    |
    +-- circuit_breaker->record_success() / record_failure()
    |       --> DB: UPDATE local_integrationhub_cb
    |
    +-- log_request(...)
    |       --> DB: INSERT local_integrationhub_log
    |
    v
return gateway_response(success, httpstatus, body, error, latency, attempts)
```

### 7.2 Event Bridge flow (automatic)

```
User action in Moodle
    |
    v
Moodle fires event (e.g. \core\event\user_created)
    |
    v
event\observer::handle_event($event)
    |
    +-- DB: SELECT rules WHERE eventname = ? AND enabled = 1
    +-- No rules --> return
    +-- Compute SHA1(eventname + objectid + userid + crud)
    +-- Cache hit --> return (duplicate)
    +-- For each rule: queue_adhoc_task({ruleid, eventdata})
    +-- cache->set(signature, 1)

[Moodle cron]
    |
    v
task\dispatch_event_task::execute()
    +-- Load rule + service from DB
    +-- Interpolate template
    +-- gateway->request(...)
    +-- On failure --> INSERT local_integrationhub_dlq
```

---

## 8. Resilience: Circuit Breaker and Retry

### 8.1 Circuit Breaker

The Circuit Breaker prevents a downed service from degrading Moodle's performance.

#### States

```
              success
CLOSED <--------------------- HALFOPEN
  |                               ^
  |  threshold reached            | cooldown expired
  v                               |
OPEN ----------------------------+
```

| State | Behavior |
|-------|----------|
| **CLOSED** | Normal operation. All requests pass through. |
| **OPEN** | Blocked. Requests fail immediately without calling the service. |
| **HALFOPEN** | Testing. One request is allowed through. Success -> CLOSED. Failure -> OPEN. |

#### Per-service configuration

| Parameter | DB Column | Description | Default |
|-----------|-----------|-------------|---------|
| Failure threshold | `cb_failure_threshold` | Consecutive failures to open the circuit | `5` |
| Cooldown | `cb_cooldown` | Seconds before transitioning to HALFOPEN | `30` |

#### Manual reset

From the dashboard, the **"Reset Circuit"** button forces the state to CLOSED and resets the failure counter.

### 8.2 Retry Policy

Retries use **exponential backoff** capped at 60 seconds:

```
Attempt 1: immediate
Attempt 2: wait backoff * 2^0 = 1s
Attempt 3: wait backoff * 2^1 = 2s
Attempt 4: wait backoff * 2^2 = 4s
...
Maximum cap: 60 seconds
```

With `max_retries = 3` and `retry_backoff = 1`:
- Total attempts: 4 (1 initial + 3 retries)
- Delays: 1s, 2s, 4s

> **Important:** Retries happen **synchronously** within the request context. For long-running integrations, consider using the Event Bridge (which uses asynchronous adhoc tasks).

---

## 9. Supported Transports

### 9.1 HTTP / REST

**Driver:** `transport\http`

- Uses native PHP cURL
- Supports: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`
- Automatic headers: `Content-Type: application/json`, `Accept: application/json`
- Authentication: `Authorization: Bearer {token}` or `X-API-Key: {token}`
- Payload: JSON-encoded in the request body (or query string for GET)
- Success: HTTP 2xx

**URL constructed as:** `{base_url}/{endpoint}`

### 9.2 AMQP / RabbitMQ

**Driver:** `transport\amqp`

- Requires `php-amqplib/php-amqplib`
- Supports `amqp://` (port 5672) and `amqps://` (port 5671, SSL)
- The `$endpoint` in `gateway->request()` acts as the **routing key** (overrides the service default)
- Publishes messages as JSON to the configured exchange
- Can auto-declare queues (`queue_declare` in the connection URL)

**Connection URL format:**
```
amqp://user:password@host:port/vhost?exchange=X&routing_key=Y&queue_declare=Z&dlq=DLQ
```

### 9.3 SOAP

**Driver:** `transport\soap`

- Uses PHP's `SoapClient` extension
- The `$endpoint` is the SOAP method name to invoke
- The `$payload` is passed as parameters to the method

---

## 10. Database Schema

### Table: `local_integrationhub_svc` — Services

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `name` | VARCHAR(255) | Unique service slug (unique index) |
| `type` | VARCHAR(10) | `rest`, `amqp`, `soap` |
| `base_url` | VARCHAR(1333) | Base URL or AMQP connection string |
| `auth_type` | VARCHAR(20) | `bearer` or `apikey` |
| `auth_token` | TEXT | Token or API key |
| `timeout` | INT | Timeout in seconds |
| `max_retries` | INT | Maximum retry attempts |
| `retry_backoff` | INT | Initial backoff in seconds |
| `cb_failure_threshold` | INT | Failures to open the circuit |
| `cb_cooldown` | INT | Cooldown seconds |
| `response_queue` | VARCHAR(255) | AMQP queue for inbound responses |
| `enabled` | INT(1) | `1` = active, `0` = disabled |
| `timecreated` | INT | Creation timestamp |
| `timemodified` | INT | Last modification timestamp |

### Table: `local_integrationhub_cb` — Circuit Breaker

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `serviceid` | INT | FK -> `local_integrationhub_svc.id` (unique) |
| `state` | VARCHAR(10) | `closed`, `open`, `halfopen` |
| `failure_count` | INT | Consecutive failure counter |
| `last_failure` | INT | Timestamp of last failure |
| `timemodified` | INT | Last update timestamp |

### Table: `local_integrationhub_log` — Request Log

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `serviceid` | INT | FK -> `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(1333) | Called endpoint |
| `http_method` | VARCHAR(10) | HTTP method used |
| `http_status` | INT | HTTP response code |
| `latency_ms` | INT | Latency in milliseconds |
| `attempt_count` | INT | Number of attempts made |
| `success` | INT(1) | `1` = success, `0` = failure |
| `error_message` | TEXT | Error message (if failed) |
| `direction` | VARCHAR(10) | `outbound` or `inbound` |
| `timecreated` | INT | Request timestamp |

> **Auto-purge:** The log is automatically purged to maintain a maximum number of entries (configurable in plugin settings, default: 500).

### Table: `local_integrationhub_rules` — Event Bridge Rules

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `eventname` | VARCHAR(255) | Full event class name |
| `serviceid` | INT | FK -> `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(255) | Endpoint override (optional) |
| `http_method` | VARCHAR(10) | HTTP method for REST |
| `payload_template` | TEXT | JSON template with placeholders |
| `enabled` | INT(1) | `1` = active, `0` = disabled |
| `timecreated` | INT | Creation timestamp |
| `timemodified` | INT | Last modification timestamp |

### Table: `local_integrationhub_dlq` — Dead Letter Queue

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `eventname` | VARCHAR(255) | Failed event name |
| `serviceid` | INT | FK -> `local_integrationhub_svc.id` |
| `payload` | TEXT | JSON payload that failed to deliver |
| `error_message` | TEXT | Reason for failure |
| `timecreated` | INT | Failure timestamp |

---

## 11. PHP Class Reference

### `\local_integrationhub\gateway`

```php
// Singleton
gateway::instance(): self

// Main method
gateway->request(
    string $servicename,
    string $endpoint = '/',
    array $payload = [],
    string $method = ''
): gateway_response
```

### `\local_integrationhub\gateway_response`

```php
// Public properties
$response->success    // bool
$response->httpstatus // int|null
$response->body       // string|null
$response->error      // string|null
$response->latencyms  // int
$response->attempts   // int

// Methods
$response->is_ok(): bool
$response->json(bool $assoc = true): mixed
```

### `\local_integrationhub\service\registry`

```php
registry::get_service(string $name): \stdClass|false
registry::get_service_by_id(int $id): \stdClass
registry::get_all_services(): array
registry::create_service(\stdClass $data): int
registry::update_service(int $id, \stdClass $data): bool
registry::delete_service(int $id): bool
```

### `\local_integrationhub\service\circuit_breaker`

```php
circuit_breaker::from_service(\stdClass $service): self
$cb->is_available(): bool
$cb->record_success(): void
$cb->record_failure(): void
$cb->get_state(): \stdClass
$cb->get_state_label(): string  // 'CLOSED', 'OPEN', 'HALFOPEN'
$cb->reset(): void
```

### `\local_integrationhub\service\retry_policy`

```php
retry_policy::from_service(\stdClass $service): self
$policy->execute(callable $operation): mixed
$policy->get_total_attempts(): int
```

---

## 12. Internal AJAX Endpoint

The endpoint `/local/integrationhub/ajax.php` provides AJAX functionality for the UI.

### `action=preview_payload`

Previews a payload template with mock data.

**Method:** `GET` or `POST`
**Requires:** Active session + `local/integrationhub:manage` capability

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `action` | string | `preview_payload` |
| `template` | string | JSON template with placeholders |
| `eventname` | string | Event name (for mock data context) |
| `sesskey` | string | Moodle session key |

**Success response:**
```json
{
  "success": true,
  "payload": { "event": "\\core\\event\\user_created", "user_id": 5 },
  "raw": "{\"event\": \"\\\\core\\\\event\\\\user_created\", \"user_id\": 5}"
}
```

**JSON error response:**
```json
{
  "success": false,
  "error": "Syntax error",
  "raw": "{\"event\": \"{{eventname}\""
}
```

---

## 13. Roles and Permissions

| Capability | Description | Suggested role |
|------------|-------------|----------------|
| `local/integrationhub:manage` | Create, edit, and delete services and rules | Site administrator |
| `local/integrationhub:view` | View dashboard, logs, and service status | Manager, Administrator |

External plugins using the Gateway **do not require special permissions** — permission checks are the responsibility of the calling plugin.

---

## 14. Scheduled Tasks

| Task | Class | Frequency | Description |
|------|-------|-----------|-------------|
| Consume Responses | `task\consume_responses_task` | Every minute | Consumes response messages from configured AMQP queues |
| Dispatch Event | `task\dispatch_event_task` | Adhoc (immediate) | Processes one queued event and sends it to the target service |

Adhoc tasks run on the next Moodle cron cycle (typically every minute).

---

## 15. File Structure

```
local/integrationhub/
├── amd/
│   ├── src/
│   │   ├── dashboard.js      # Dashboard logic (charts, service form)
│   │   ├── rules.js          # Rules form logic
│   │   └── queue.js          # Queue monitor
│   └── build/                # Minified files (generated by grunt)
├── assets/
│   └── min/
│       └── chart.umd.min.js  # Chart.js (local, avoids CDN)
├── classes/
│   ├── event/
│   │   ├── observer.php          # Universal event listener
│   │   └── webhook_received.php  # Event for inbound webhooks
│   ├── service/
│   │   ├── circuit_breaker.php   # Circuit state management
│   │   ├── registry.php          # Service CRUD
│   │   └── retry_policy.php      # Retry logic with backoff
│   ├── task/
│   │   ├── dispatch_event_task.php    # Adhoc: dispatch event to service
│   │   └── consume_responses_task.php # Scheduled: consume AMQP responses
│   ├── transport/
│   │   ├── contract.php        # Common interface for all drivers
│   │   ├── http.php            # REST/HTTP driver via cURL
│   │   ├── amqp.php            # RabbitMQ driver via php-amqplib
│   │   ├── amqp_helper.php     # AMQP connection helpers
│   │   ├── soap.php            # SOAP driver
│   │   └── transport_utils.php # Shared utilities (success/error result)
│   ├── gateway.php             # Main orchestrator (public API)
│   ├── gateway_response.php    # Response value object
│   └── webhook_handler.php     # Inbound webhook handler
├── db/
│   ├── access.php    # Capability definitions
│   ├── caches.php    # Cache definitions (event_dedupe)
│   ├── events.php    # Universal observer registration
│   ├── install.xml   # DB schema (5 tables)
│   ├── tasks.php     # Scheduled task definitions
│   └── upgrade.php   # DB upgrade script
├── docs/
│   ├── documento_maestro.md      # This document (English)
│   ├── respuestas_arquitectura.md
│   └── validation_scenarios.md
├── lang/
│   ├── en/local_integrationhub.php  # English strings
│   └── es/local_integrationhub.php  # Spanish strings
├── ajax.php       # Internal AJAX endpoint
├── events.php     # Sent events page
├── index.php      # Main dashboard (services)
├── logs.php       # Log viewer
├── queue.php      # Queue monitor / DLQ
├── rules.php      # Rules management
├── settings.php   # Plugin admin settings
├── version.php    # Plugin version
└── webhook.php    # Inbound webhook endpoint
```
