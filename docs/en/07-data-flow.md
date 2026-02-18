# Data Flow

This document provides detailed end-to-end flow diagrams for every execution path in MIH.

---

## Path 1: Direct Gateway Call (Synchronous)

Used when a plugin calls `gateway->request()` directly and needs the response immediately.

```
Plugin PHP Code
    │
    ▼
gateway::instance()
    │   (Singleton — same instance throughout the request)
    │
    ▼
gateway->request('service-name', '/endpoint', $payload, 'POST')
    │
    ├─── [1] Service Resolution ─────────────────────────────────────────
    │         service\registry::get_service('service-name')
    │         → SELECT * FROM local_integrationhub_svc WHERE name = ?
    │         → If not found: throw moodle_exception('service_not_found')
    │         → If disabled: throw moodle_exception('service_disabled')
    │
    ├─── [2] Circuit Breaker Check ──────────────────────────────────────
    │         circuit_breaker::from_service($service)
    │         → SELECT * FROM local_integrationhub_cb WHERE serviceid = ?
    │         → state = 'open' AND time() - last_failure < cooldown
    │             → throw moodle_exception('circuit_open')
    │         → state = 'open' AND cooldown expired
    │             → UPDATE cb SET state = 'halfopen'
    │             → continue (probe request)
    │         → state = 'closed' OR 'halfopen'
    │             → continue
    │
    ├─── [3] Transport Selection ────────────────────────────────────────
    │         get_transport_driver($service->type)
    │         → 'rest'  → new transport\http()
    │         → 'amqp'  → new transport\amqp()
    │         → 'soap'  → new transport\soap()
    │
    ├─── [4] Retry Loop ─────────────────────────────────────────────────
    │         retry_policy::from_service($service)->execute(fn)
    │         │
    │         ├─ Attempt 1:
    │         │   transport->execute($service, $endpoint, $payload, $method)
    │         │   → [HTTP] Build URL, set headers, cURL request
    │         │   → [AMQP] Parse URL, connect, publish message
    │         │   → [SOAP] SoapClient->__soapCall(method, params)
    │         │   → Returns result array OR throws Exception
    │         │
    │         ├─ If Exception: sleep(backoff * 2^0) → Attempt 2
    │         ├─ If Exception: sleep(backoff * 2^1) → Attempt 3
    │         └─ If Exception: sleep(backoff * 2^2) → Attempt 4 (if max_retries=3)
    │
    ├─── [5] Circuit Breaker Update ─────────────────────────────────────
    │         → On success: cb->record_success()
    │             UPDATE cb SET failure_count=0, state='closed'
    │         → On failure: cb->record_failure()
    │             UPDATE cb SET failure_count++, last_failure=now()
    │             If failure_count >= threshold: SET state='open'
    │
    ├─── [6] Request Logging ────────────────────────────────────────────
    │         INSERT INTO local_integrationhub_log (
    │             serviceid, endpoint, http_method, http_status,
    │             latency_ms, attempt_count, success, error_message,
    │             direction, timecreated
    │         )
    │         → Auto-purge if count > max_log_entries
    │
    └─── [7] Return ─────────────────────────────────────────────────────
              return new gateway_response(
                  success:    $result['success'],
                  httpstatus: $result['httpstatus'],
                  body:       $result['body'],
                  error:      $result['error'],
                  latencyms:  $result['latencyms'],
                  attempts:   $result['attempts']
              )
```

---

## Path 2: Event Bridge (Asynchronous)

Used when an administrator creates a rule mapping a Moodle event to a service.

### Phase A: Event Capture (Synchronous, in the user's request)

```
User action in Moodle
    │  (e.g., admin creates a user account)
    ▼
Moodle fires event: \core\event\user_created
    │
    ▼
event\observer::handle_event($event)   [registered in db/events.php]
    │
    ├─── [1] Rule Lookup ────────────────────────────────────────────────
    │         SELECT * FROM local_integrationhub_rules
    │         WHERE eventname = '\core\event\user_created'
    │         AND enabled = 1
    │         → If empty: return (nothing to do)
    │
    ├─── [2] Deduplication ──────────────────────────────────────────────
    │         $sig = sha1(eventname + objectid + userid + crud)
    │         $cache = cache::make('local_integrationhub', 'event_dedupe')
    │         → If $cache->get($sig): return (duplicate, skip)
    │         → $cache->set($sig, 1)  [TTL: 60 seconds]
    │
    └─── [3] Task Queuing ───────────────────────────────────────────────
              For each matching rule:
                  $task = new dispatch_event_task()
                  $task->set_custom_data([
                      'ruleid'    => $rule->id,
                      'eventdata' => $event->get_data(),
                      'attempts'  => 0,
                  ])
                  core\task\manager::queue_adhoc_task($task)
                  → INSERT INTO task_adhoc (...)

[User's request completes — no waiting for integration]
```

### Phase B: Event Dispatch (Asynchronous, in Moodle cron)

```
Moodle cron runs (typically every 1 minute)
    │
    ▼
dispatch_event_task::execute()
    │
    ├─── [1] Load Rule ──────────────────────────────────────────────────
    │         SELECT * FROM local_integrationhub_rules WHERE id = ?
    │         → If not found or disabled: mtrace + return (skip silently)
    │
    ├─── [2] Load Service ───────────────────────────────────────────────
    │         service_registry::get_service_by_id($rule->serviceid)
    │         → If not found or disabled: mtrace + return (skip silently)
    │
    ├─── [3] Payload Preparation ────────────────────────────────────────
    │         If template is empty:
    │             $payload = $eventdata (raw event data array)
    │         Else:
    │             For each {{key}} in template:
    │                 Replace with $eventdata[$key]
    │                 (strings are JSON-escaped, numbers are raw)
    │             $payload = json_decode($interpolated_template, true)
    │             If JSON invalid: throw moodle_exception (task retries)
    │
    ├─── [4] Method Resolution ──────────────────────────────────────────
    │         → AMQP service: method = 'AMQP'
    │         → Rule has http_method: use it
    │         → Default: 'POST'
    │
    ├─── [5] Gateway Call ───────────────────────────────────────────────
    │         gateway::instance()->request(
    │             $service->name, $endpoint, $payload, $method
    │         )
    │         → Full Path 1 flow (circuit breaker, retry, log)
    │
    ├─── [6a] On Success ────────────────────────────────────────────────
    │         mtrace("Success: HTTP 200")
    │         Task completes normally
    │
    └─── [6b] On Failure ────────────────────────────────────────────────
              $data->attempts++
              UPDATE task_adhoc SET customdata = json_encode($data)
              │
              ├─ If attempts < 5:
              │   throw $e  → Moodle retries the task (with delay)
              │
              └─ If attempts >= 5:
                  move_to_dlq($rule, $payload, $error)
                  → INSERT INTO local_integrationhub_dlq (...)
                  return  (task completes, no more retries)
```

---

## Path 3: AMQP Response Consumption (Scheduled)

Used when a service sends responses back via a RabbitMQ queue.

```
Moodle cron (every minute)
    │
    ▼
consume_responses_task::execute()
    │
    ├─── [1] Find Services with Response Queues ─────────────────────────
    │         SELECT * FROM local_integrationhub_svc
    │         WHERE type = 'amqp'
    │         AND response_queue IS NOT NULL
    │         AND enabled = 1
    │
    └─── [2] For each service: ──────────────────────────────────────────
              Connect to RabbitMQ (amqp_helper::create_connection)
              Consume messages from response_queue
              For each message:
                  Parse JSON body
                  Log to local_integrationhub_log (direction='inbound')
                  Acknowledge message (basic_ack)
              Close connection
```

---

## Path 4: Circuit Breaker State Machine

```
Initial state: CLOSED (failure_count = 0)

CLOSED
  │
  ├─ Request succeeds → stay CLOSED (failure_count stays 0)
  │
  └─ Request fails
       failure_count++
       │
       ├─ failure_count < threshold → stay CLOSED
       │
       └─ failure_count >= threshold
            → transition to OPEN
            → last_failure = time()

OPEN
  │
  ├─ New request arrives:
  │   time() - last_failure < cooldown
  │   → reject immediately (throw circuit_open)
  │
  └─ New request arrives:
       time() - last_failure >= cooldown
       → transition to HALFOPEN
       → allow one probe request

HALFOPEN
  │
  ├─ Probe request succeeds
  │   → transition to CLOSED
  │   → failure_count = 0
  │
  └─ Probe request fails
       → transition to OPEN
       → last_failure = time()
```

---

## Database Write Summary

| Operation | Tables Written |
|-----------|---------------|
| Service created | `svc`, `cb` (initial state) |
| Request made | `log`, `cb` (state update) |
| Event rule matched | `task_adhoc` (queue) |
| Task dispatched successfully | `log`, `cb` |
| Task failed (< 5 attempts) | `log`, `cb`, `task_adhoc` (updated custom_data) |
| Task permanently failed | `log`, `cb`, `dlq` |
| DLQ replayed | `task_adhoc` (new task) |
| Circuit reset | `cb` |
| Log purged | `log` (old rows deleted) |
