# System Architecture

This document describes the internal architecture of Moodle Integration Hub, the responsibilities of each component, and the design decisions behind them.

---

## High-Level Overview

MIH is structured around two independent but complementary execution paths:

```
+=====================================================================+
|                         MOODLE CORE                                 |
|                                                                     |
|  [Any Moodle Event] ──► [Universal Observer] ──► [Adhoc Task]      |
|                                                         │           |
|  [External Plugin]  ──────────────────────────────►    │           |
|                                                         │           |
|                                              ┌──────────▼────────┐ |
|                                              │   gateway.php     │ |
|                                              │  (Orchestrator)   │ |
|                                              └──────────┬────────┘ |
|                                                         │           |
|                              ┌──────────────────────────┤           |
|                              │                          │           |
|                    ┌─────────▼──────┐        ┌─────────▼──────┐   |
|                    │ circuit_breaker│        │  retry_policy  │   |
|                    │  (Guard)       │        │  (Resilience)  │   |
|                    └─────────┬──────┘        └─────────┬──────┘   |
|                              └──────────┬───────────────┘           |
|                                         │                           |
|                    ┌────────────────────┼────────────────────┐     |
|                    │                    │                    │     |
|          ┌─────────▼──────┐  ┌─────────▼──────┐  ┌─────────▼──┐  |
|          │ transport\http │  │ transport\amqp │  │transport\  │  |
|          │  (REST/cURL)   │  │  (RabbitMQ)    │  │  soap      │  |
|          └─────────┬──────┘  └─────────┬──────┘  └─────────┬──┘  |
+=====================================================================+
                     │                   │                   │
          ┌──────────▼───────────────────▼───────────────────▼──────┐
          │                   External Services                       │
          │   REST API  │  RabbitMQ Broker  │  SOAP Web Service      │
          └────────────────────────────────────────────────────────┘
```

---

## Component Map

### Core Layer

| Class | Namespace | File | Role |
|-------|-----------|------|------|
| `gateway` | `local_integrationhub` | `classes/gateway.php` | **Main orchestrator.** Singleton. Public API for all plugins. Coordinates service resolution, circuit breaking, transport selection, retry, and logging. |
| `gateway_response` | `local_integrationhub` | `classes/gateway_response.php` | **Immutable value object.** Wraps the result of every request regardless of transport. |

### Service Layer

| Class | Namespace | File | Role |
|-------|-----------|------|------|
| `registry` | `local_integrationhub\service` | `classes/service/registry.php` | **Data access.** CRUD operations for the `local_integrationhub_svc` table. Also initializes the circuit breaker record when a service is created. |
| `circuit_breaker` | `local_integrationhub\service` | `classes/service/circuit_breaker.php` | **Fault tolerance.** Tracks failure counts and manages CLOSED/OPEN/HALFOPEN state transitions. Reads/writes `local_integrationhub_cb`. |
| `retry_policy` | `local_integrationhub\service` | `classes/service/retry_policy.php` | **Resilience.** Executes a callable with configurable retry attempts and exponential backoff. |

### Transport Layer

| Class | Namespace | File | Role |
|-------|-----------|------|------|
| `contract` | `local_integrationhub\transport` | `classes/transport/contract.php` | **Interface.** Defines the `execute()` contract all drivers must implement. |
| `http` | `local_integrationhub\transport` | `classes/transport/http.php` | **REST driver.** Uses native PHP cURL. Supports GET, POST, PUT, PATCH, DELETE. |
| `amqp` | `local_integrationhub\transport` | `classes/transport/amqp.php` | **RabbitMQ driver.** Uses `php-amqplib`. Publishes JSON messages to exchanges or queues. |
| `amqp_helper` | `local_integrationhub\transport` | `classes/transport/amqp_helper.php` | **AMQP utilities.** Centralizes connection creation (plain and SSL) and queue declaration. |
| `soap` | `local_integrationhub\transport` | `classes/transport/soap.php` | **SOAP driver.** Uses PHP's native `SoapClient`. |
| `transport_utils` | `local_integrationhub\transport` | `classes/transport/transport_utils.php` | **Trait.** Shared helpers for building `success_result` and `error_result` arrays. |

### Event Layer

| Class | Namespace | File | Role |
|-------|-----------|------|------|
| `observer` | `local_integrationhub\event` | `classes/event/observer.php` | **Universal listener.** Registered against `\core\event\base` to catch every event in Moodle. Performs rule lookup, deduplication, and adhoc task queuing. |
| `webhook_received` | `local_integrationhub\event` | `classes/event/webhook_received.php` | **Custom event.** Fired when an inbound webhook is received, allowing other plugins to react. |

### Task Layer

| Class | Namespace | File | Role |
|-------|-----------|------|------|
| `dispatch_event_task` | `local_integrationhub\task` | `classes/task/dispatch_event_task.php` | **Adhoc task.** Processes one queued event: loads the rule, interpolates the template, calls the Gateway, handles DLQ on permanent failure. |
| `consume_responses_task` | `local_integrationhub\task` | `classes/task/consume_responses_task.php` | **Scheduled task.** Runs every minute. Consumes inbound messages from configured AMQP response queues. |
| `queue_manager` | `local_integrationhub\task` | `classes/task/queue_manager.php` | **Queue utilities.** Shared logic for queue monitoring and DLQ management. |

---

## Execution Paths

### Path 1: Direct Plugin Call (Synchronous)

```
Plugin code
  └─► gateway::instance()->request(name, endpoint, payload, method)
        ├─► registry::get_service(name)          [DB read]
        ├─► circuit_breaker::is_available()      [DB read]
        ├─► get_transport_driver(type)           [factory]
        ├─► retry_policy::execute(fn)            [loop]
        │     └─► transport::execute(...)        [network]
        ├─► circuit_breaker::record_*()          [DB write]
        ├─► log_request(...)                     [DB write]
        └─► return gateway_response
```

This path is **synchronous** — the calling plugin waits for the result. Use it when you need the response immediately (e.g., to validate data, get an ID, or display a result to the user).

### Path 2: Event Bridge (Asynchronous)

```
Moodle action
  └─► event fired
        └─► observer::handle_event(event)
              ├─► DB: find matching rules
              ├─► cache: deduplication check
              └─► queue_adhoc_task(dispatch_event_task)  [non-blocking]

[Moodle cron, ~1 min later]
  └─► dispatch_event_task::execute()
        ├─► load rule + service from DB
        ├─► interpolate payload template
        ├─► gateway::instance()->request(...)
        └─► on permanent failure: move_to_dlq()
```

This path is **asynchronous** — the user action completes immediately. The integration happens in the background. Use it for fire-and-forget notifications, webhooks, and event streaming.

---

## Key Design Decisions

### Singleton Gateway

`gateway` is a singleton (`instance()` pattern). This avoids the overhead of re-instantiating the class on every call and makes it easy to mock in tests.

### Transport as Strategy Pattern

The transport layer uses the Strategy pattern via the `contract` interface. The Gateway selects the correct driver at runtime based on the service's `type` field. Adding a new transport (e.g., gRPC) requires only implementing `contract` and registering it in `get_transport_driver()`.

### Retry Inside Transport vs. Gateway

The retry logic lives in the Gateway (via `retry_policy`), not inside each transport driver. This keeps drivers simple and ensures consistent retry behavior regardless of transport.

### Deduplication via Cache

Event deduplication uses Moodle's application cache (`local_integrationhub/event_dedupe`). The cache key is a SHA1 hash of `eventname + objectid + userid + crud`. This prevents duplicate adhoc tasks from being queued when the same logical event fires multiple times (e.g., due to Moodle's observer system calling the same event from multiple contexts).

### DLQ After 5 Attempts

Moodle's adhoc task system has its own retry mechanism. `dispatch_event_task` tracks its own attempt counter in `custom_data`. After 5 total attempts, it stops rethrowing and instead writes the failed payload to `local_integrationhub_dlq`, preventing infinite retry loops.

### Auto-purging Logs

The log table is automatically pruned after every write. The maximum number of entries is configurable (default: 500). This prevents unbounded database growth in high-traffic deployments.

---

## Database Relationships

```
local_integrationhub_svc (1)
    ├── (1) local_integrationhub_cb      [circuit state]
    ├── (N) local_integrationhub_log     [request log]
    ├── (N) local_integrationhub_rules   [event rules]
    └── (N) local_integrationhub_dlq     [dead letters]
```

All foreign keys cascade on delete — removing a service cleans up all associated records.
