# Resilience: Circuit Breaker, Retry Policy, and Dead Letter Queue

MIH is built with resilience as a first-class concern. This document explains the three mechanisms that protect Moodle from external service failures.

---

## Overview

| Mechanism | Purpose | Scope |
|-----------|---------|-------|
| **Circuit Breaker** | Prevents calling services that are known to be down | Per-service |
| **Retry Policy** | Automatically retries transient failures | Per-request |
| **Dead Letter Queue** | Stores permanently failed events for review | Per event rule |

These three mechanisms work together in sequence:

```
gateway->request()
    │
    ├─ Circuit Breaker: Is the service available?
    │   └─ OPEN + cooldown not expired → fail immediately (no network call)
    │
    ├─ Retry Policy: Execute with retries
    │   ├─ Attempt 1 → transport.execute()
    │   ├─ Failure → wait backoff → Attempt 2
    │   ├─ Failure → wait backoff*2 → Attempt 3
    │   └─ ...up to max_retries
    │
    ├─ Circuit Breaker: Record outcome
    │   ├─ Success → record_success() (may close circuit)
    │   └─ Failure → record_failure() (may open circuit)
    │
    └─ Return gateway_response
```

For Event Bridge tasks, if the Gateway fails after all retries, the task is retried by Moodle's cron up to 5 times total before being moved to the DLQ.

---

## Circuit Breaker

### States

The circuit breaker has three states:

```
         [threshold reached]
CLOSED ─────────────────────► OPEN
  ▲                             │
  │ [success]                   │ [cooldown expired]
  │                             ▼
HALFOPEN ◄───────────────────────
```

| State | Behavior |
|-------|----------|
| **CLOSED** | Normal. All requests pass through. Failure counter increments on each failure. |
| **OPEN** | Tripped. All requests fail immediately without a network call. Protects the system from piling up requests to a downed service. |
| **HALFOPEN** | Recovery probe. One request is allowed through. If it succeeds → CLOSED. If it fails → back to OPEN. |

### State Transitions

| From | To | Condition |
|------|----|-----------|
| CLOSED | OPEN | `failure_count >= cb_failure_threshold` |
| OPEN | HALFOPEN | `time() - last_failure >= cb_cooldown` |
| HALFOPEN | CLOSED | Next request succeeds |
| HALFOPEN | OPEN | Next request fails |

### Configuration

Per-service settings (configurable in the dashboard):

| Setting | DB Column | Default | Description |
|---------|-----------|---------|-------------|
| Failure Threshold | `cb_failure_threshold` | `5` | Consecutive failures before opening |
| Cooldown | `cb_cooldown` | `30` | Seconds before attempting recovery |

### Implementation Details

The circuit breaker state is stored in `local_integrationhub_cb`:

```sql
SELECT state, failure_count, last_failure
FROM local_integrationhub_cb
WHERE serviceid = ?
```

`is_available()` logic:

```php
public function is_available(): bool {
    if ($this->state === 'closed') {
        return true;
    }
    if ($this->state === 'open') {
        // Check if cooldown has expired
        if (time() - $this->last_failure >= $this->cooldown) {
            $this->transition_to('halfopen');
            return true; // Allow one probe request
        }
        return false; // Still cooling down
    }
    // halfopen: allow one request through
    return true;
}
```

`record_failure()` logic:

```php
public function record_failure(): void {
    $this->failure_count++;
    $this->last_failure = time();

    if ($this->failure_count >= $this->threshold) {
        $this->transition_to('open');
    }
    $this->save();
}
```

`record_success()` logic:

```php
public function record_success(): void {
    $this->failure_count = 0;
    if ($this->state !== 'closed') {
        $this->transition_to('closed');
    }
    $this->save();
}
```

### Manual Reset

From the dashboard, click **Reset Circuit** to force a service back to CLOSED:

```php
public function reset(): void {
    $this->state         = 'closed';
    $this->failure_count = 0;
    $this->last_failure  = 0;
    $this->save();
}
```

Use this when:
- You have confirmed the external service has recovered
- You want to test a service without waiting for the cooldown
- A false positive tripped the circuit (e.g., a one-time network blip)

---

## Retry Policy

### Algorithm

MIH uses **exponential backoff** — each retry waits twice as long as the previous one, capped at 60 seconds:

```
delay(attempt) = min(backoff * 2^(attempt-1), 60)
```

With `max_retries = 3` and `retry_backoff = 1`:

| Attempt | Delay Before This Attempt |
|---------|--------------------------|
| 1 (initial) | 0s (immediate) |
| 2 (retry 1) | 1s |
| 3 (retry 2) | 2s |
| 4 (retry 3) | 4s |

Total maximum wait: 7 seconds (1 + 2 + 4).

With `max_retries = 5` and `retry_backoff = 2`:

| Attempt | Delay |
|---------|-------|
| 1 | 0s |
| 2 | 2s |
| 3 | 4s |
| 4 | 8s |
| 5 | 16s |
| 6 | 32s |

Total maximum wait: 62 seconds.

### Configuration

Per-service settings:

| Setting | DB Column | Default | Description |
|---------|-----------|---------|-------------|
| Max Retries | `max_retries` | `3` | Additional attempts after the first failure |
| Initial Backoff | `retry_backoff` | `1` | Seconds before the first retry |

### What Triggers a Retry

The retry policy retries on **any exception** thrown by the transport driver. This includes:
- Network timeouts (cURL timeout)
- Connection refused
- DNS resolution failure
- AMQP connection errors

It does **not** automatically retry based on HTTP status codes. A `500 Internal Server Error` response is returned as a failed `gateway_response` but does not trigger a retry by default (the transport returns a result, not an exception).

> **Design note:** This is intentional. HTTP 5xx errors may indicate a permanent server-side issue (e.g., a bug in the external API). Retrying them blindly could cause duplicate processing on the external side. If you need to retry on 5xx, implement that logic in your calling code.

### Implementation

```php
public function execute(callable $operation): mixed {
    $lastexception = null;

    for ($attempt = 1; $attempt <= $this->maxattempts; $attempt++) {
        try {
            return $operation($attempt);
        } catch (\Exception $e) {
            $lastexception = $e;

            if ($attempt < $this->maxattempts) {
                $delay = min($this->backoff * (2 ** ($attempt - 1)), 60);
                sleep($delay);
            }
        }
    }

    throw $lastexception;
}
```

---

## Dead Letter Queue (DLQ)

### Purpose

The DLQ is a safety net for the Event Bridge. When an event cannot be delivered after all retry attempts, it is stored in the DLQ instead of being silently dropped.

### When Events Go to the DLQ

1. `dispatch_event_task` fails (exception thrown)
2. Moodle retries the task (up to Moodle's own retry limit)
3. MIH tracks its own attempt counter in `custom_data`
4. After **5 total attempts**, the task calls `move_to_dlq()` and returns without rethrowing
5. Moodle marks the task as complete (no more retries)

### DLQ Table Structure

```sql
local_integrationhub_dlq:
  id            INT          -- Primary key
  eventname     VARCHAR(255) -- Event class name
  serviceid     INT          -- Target service ID
  payload       TEXT         -- JSON payload that failed
  error_message TEXT         -- Last error message
  timecreated   INT          -- Timestamp of failure
```

### Reviewing the DLQ

Navigate to `/local/integrationhub/queue.php`:

- View all failed events with their error messages
- See the exact payload that was attempted
- Identify patterns (e.g., all failures for one service = service is down)

### Replaying DLQ Events

Click **Replay** on any DLQ entry to re-queue it as a new adhoc task. The task will go through the full dispatch flow again (template interpolation, Gateway call, retries).

Use replay when:
- The external service has recovered
- You fixed a bug in the payload template
- A network issue was temporary

### Deleting DLQ Events

Click **Delete** to permanently remove a DLQ entry. Use this when:
- The event is no longer relevant
- The external service no longer exists
- You have processed the event manually

---

## Tuning Recommendations

### High-Traffic Production

```
cb_failure_threshold = 10    (more tolerance for occasional failures)
cb_cooldown          = 60    (longer recovery window)
max_retries          = 2     (fewer retries to avoid blocking cron)
retry_backoff        = 1     (fast retries)
timeout              = 3     (short timeout to fail fast)
```

### Low-Traffic / Development

```
cb_failure_threshold = 3     (trip quickly to catch issues)
cb_cooldown          = 10    (recover quickly for testing)
max_retries          = 3     (standard retries)
retry_backoff        = 1
timeout              = 10    (more lenient for slow dev servers)
```

### Critical Integrations (Must Not Lose Events)

```
max_retries          = 5     (more retries before DLQ)
retry_backoff        = 2     (longer backoff)
cb_failure_threshold = 20    (very tolerant circuit)
cb_cooldown          = 120   (long cooldown)
```

And monitor the DLQ regularly to catch any events that do end up there.
