# Database Schema Reference

This document describes every table in the MIH database schema, including column definitions, constraints, indexes, and usage notes.

---

## Overview

MIH uses five tables, all prefixed with `local_integrationhub_`:

| Table | Purpose | Rows (typical) |
|-------|---------|----------------|
| `svc` | Registered external services | 1–50 |
| `cb` | Circuit breaker state (one row per service) | Same as `svc` |
| `log` | Request/response log (auto-purged) | Up to `max_log_entries` |
| `rules` | Event Bridge rules | 1–500 |
| `dlq` | Dead Letter Queue for failed events | 0–∞ (manual cleanup) |

---

## `local_integrationhub_svc` — Services

The primary configuration table. Each row represents one registered external service.

### Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT | No | auto | Primary key |
| `name` | VARCHAR(255) | No | — | Unique service slug. Used as the lookup key in `gateway->request()`. No spaces. |
| `type` | VARCHAR(10) | No | `rest` | Transport type: `rest`, `amqp`, or `soap` |
| `base_url` | VARCHAR(1333) | No | — | Base URL for REST/SOAP, or full AMQP connection string |
| `auth_type` | VARCHAR(20) | Yes | `null` | Authentication method: `bearer` or `apikey` |
| `auth_token` | LONGTEXT | Yes | `null` | Token or API key value |
| `timeout` | BIGINT | No | `5` | Request timeout in seconds |
| `max_retries` | BIGINT | No | `3` | Maximum retry attempts after the first failure |
| `retry_backoff` | BIGINT | No | `1` | Initial backoff in seconds (doubles each retry) |
| `cb_failure_threshold` | BIGINT | No | `5` | Consecutive failures before opening the circuit |
| `cb_cooldown` | BIGINT | No | `30` | Seconds before attempting recovery (HALFOPEN) |
| `response_queue` | VARCHAR(255) | Yes | `null` | AMQP queue name for inbound response consumption |
| `enabled` | TINYINT(1) | No | `1` | `1` = active, `0` = disabled |
| `timecreated` | BIGINT | No | — | Unix timestamp of creation |
| `timemodified` | BIGINT | No | — | Unix timestamp of last modification |

### Indexes

| Index | Columns | Type |
|-------|---------|------|
| Primary | `id` | UNIQUE |
| `ix_name` | `name` | UNIQUE |

### Notes

- The `name` column is the public identifier used in PHP code. Changing it breaks existing `gateway->request()` calls.
- `auth_token` is stored in plaintext. For high-security environments, consider encrypting this column.
- `base_url` for AMQP services contains the full connection string including credentials.

---

## `local_integrationhub_cb` — Circuit Breaker

One row per service. Tracks the circuit breaker state.

### Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT | No | auto | Primary key |
| `serviceid` | BIGINT | No | — | FK → `local_integrationhub_svc.id` |
| `state` | VARCHAR(10) | No | `closed` | Current state: `closed`, `open`, or `halfopen` |
| `failure_count` | BIGINT | No | `0` | Consecutive failure counter. Resets to 0 on success. |
| `last_failure` | BIGINT | No | `0` | Unix timestamp of the most recent failure |
| `timemodified` | BIGINT | No | — | Unix timestamp of last state change |

### Indexes

| Index | Columns | Type |
|-------|---------|------|
| Primary | `id` | UNIQUE |
| `ix_serviceid` | `serviceid` | UNIQUE |

### Notes

- Created automatically when a service is registered (`registry::create_service()`)
- Deleted automatically when the service is deleted
- `failure_count` is a consecutive counter — it resets to 0 on any success
- `last_failure` is used to calculate when the cooldown expires: `time() - last_failure >= cooldown`

### State Values

| `state` | Meaning |
|---------|---------|
| `closed` | Normal operation |
| `open` | Circuit tripped — requests rejected |
| `halfopen` | Recovery probe — one request allowed |

---

## `local_integrationhub_log` — Request Log

Records every outbound (and inbound AMQP) request.

### Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT | No | auto | Primary key |
| `serviceid` | BIGINT | No | — | FK → `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(1333) | Yes | `null` | Endpoint path called |
| `http_method` | VARCHAR(10) | Yes | `null` | HTTP method used (`GET`, `POST`, etc.) |
| `http_status` | BIGINT | Yes | `null` | HTTP response code. `null` for AMQP. |
| `latency_ms` | BIGINT | No | `0` | Response time in milliseconds |
| `attempt_count` | BIGINT | No | `1` | Total attempts made (including retries) |
| `success` | TINYINT(1) | No | `0` | `1` = success, `0` = failure |
| `error_message` | LONGTEXT | Yes | `null` | Error description if failed |
| `direction` | VARCHAR(10) | No | `outbound` | `outbound` (MIH → service) or `inbound` (service → MIH) |
| `timecreated` | BIGINT | No | — | Unix timestamp of the request |

### Indexes

| Index | Columns | Type |
|-------|---------|------|
| Primary | `id` | UNIQUE |
| `ix_serviceid` | `serviceid` | BTREE |
| `ix_timecreated` | `timecreated` | BTREE |

### Auto-Purge

After every INSERT, the Gateway checks the total row count. If it exceeds `max_log_entries` (default: 500), the oldest rows are deleted:

```sql
DELETE FROM local_integrationhub_log
WHERE id NOT IN (
    SELECT id FROM local_integrationhub_log
    ORDER BY timecreated DESC
    LIMIT 500
)
```

### Notes

- `latency_ms` includes all retry delays — it is the total wall-clock time from request start to final response
- `attempt_count = 1` means the request succeeded on the first try
- `direction = 'inbound'` is used by `consume_responses_task` for AMQP response messages

---

## `local_integrationhub_rules` — Event Bridge Rules

Each row maps a Moodle event to a service call.

### Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT | No | auto | Primary key |
| `eventname` | VARCHAR(255) | No | — | Full PHP class name of the Moodle event |
| `serviceid` | BIGINT | No | — | FK → `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(255) | Yes | `null` | Endpoint override. For AMQP: routing key. For SOAP: method name. |
| `http_method` | VARCHAR(10) | No | `POST` | HTTP method for REST services |
| `payload_template` | LONGTEXT | Yes | `null` | JSON template with `{{variable}}` placeholders |
| `enabled` | TINYINT(1) | No | `1` | `1` = active, `0` = disabled |
| `timecreated` | BIGINT | No | — | Unix timestamp of creation |
| `timemodified` | BIGINT | No | — | Unix timestamp of last modification |

### Indexes

| Index | Columns | Type |
|-------|---------|------|
| Primary | `id` | UNIQUE |
| `ix_eventname` | `eventname` | BTREE |
| `ix_serviceid` | `serviceid` | BTREE |

### Notes

- The `eventname` index is critical for performance — the observer queries this table on every Moodle event
- Multiple rules can target the same event (fan-out: one event → multiple services)
- Multiple rules can target the same service (fan-in: multiple events → one service)
- Disabling a rule (`enabled = 0`) stops dispatch without deleting the configuration

---

## `local_integrationhub_dlq` — Dead Letter Queue

Stores events that failed to deliver after all retry attempts.

### Columns

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT | No | auto | Primary key |
| `eventname` | VARCHAR(255) | No | — | Event class name that failed |
| `serviceid` | BIGINT | No | — | Target service ID |
| `payload` | LONGTEXT | Yes | `null` | JSON-encoded payload that was attempted |
| `error_message` | LONGTEXT | Yes | `null` | Last error message from the failed attempt |
| `timecreated` | BIGINT | No | — | Unix timestamp when the event was moved to DLQ |

### Indexes

| Index | Columns | Type |
|-------|---------|------|
| Primary | `id` | UNIQUE |
| `ix_serviceid` | `serviceid` | BTREE |
| `ix_timecreated` | `timecreated` | BTREE |

### Notes

- The DLQ does **not** auto-purge — entries accumulate until manually deleted
- Replaying a DLQ entry creates a new `dispatch_event_task` adhoc task
- The `serviceid` may reference a deleted service — always check before replaying

---

## Entity Relationship Diagram

```
local_integrationhub_svc
    │ id (PK)
    │ name (UNIQUE)
    │ type
    │ base_url
    │ ...
    │
    ├──────────────────────────────────────────────────────────────────┐
    │                                                                  │
    ▼ (1:1)                                                           ▼ (1:N)
local_integrationhub_cb                              local_integrationhub_log
    serviceid (FK, UNIQUE)                               serviceid (FK)
    state                                                endpoint
    failure_count                                        http_status
    last_failure                                         latency_ms
                                                         success
    │
    ▼ (1:N)
local_integrationhub_rules
    serviceid (FK)
    eventname
    payload_template
    enabled

    │
    ▼ (1:N, on failure)
local_integrationhub_dlq
    serviceid (FK)
    eventname
    payload
    error_message
```

---

## Useful Queries

### Services with open circuits

```sql
SELECT s.name, cb.state, cb.failure_count, cb.last_failure
FROM local_integrationhub_svc s
JOIN local_integrationhub_cb cb ON cb.serviceid = s.id
WHERE cb.state != 'closed'
ORDER BY cb.last_failure DESC;
```

### Error rate per service (last 24h)

```sql
SELECT
    s.name,
    COUNT(*) AS total,
    SUM(CASE WHEN l.success = 0 THEN 1 ELSE 0 END) AS errors,
    AVG(l.latency_ms) AS avg_latency_ms
FROM local_integrationhub_log l
JOIN local_integrationhub_svc s ON s.id = l.serviceid
WHERE l.timecreated > UNIX_TIMESTAMP() - 86400
GROUP BY s.id, s.name
ORDER BY errors DESC;
```

### DLQ entries by service

```sql
SELECT s.name, COUNT(*) AS dlq_count, MAX(d.timecreated) AS last_failure
FROM local_integrationhub_dlq d
JOIN local_integrationhub_svc s ON s.id = d.serviceid
GROUP BY s.id, s.name
ORDER BY dlq_count DESC;
```

### Active rules per event

```sql
SELECT eventname, COUNT(*) AS rule_count
FROM local_integrationhub_rules
WHERE enabled = 1
GROUP BY eventname
ORDER BY rule_count DESC;
```
