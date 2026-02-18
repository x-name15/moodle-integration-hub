# Scheduled and Adhoc Tasks

MIH uses Moodle's task system for background processing. This document describes all registered tasks, their schedules, and their behavior.

---

## Overview

| Task | Type | Class | Default Schedule |
|------|------|-------|-----------------|
| Consume AMQP Responses | Scheduled | `task\consume_responses_task` | Every minute |
| Dispatch Event | Adhoc | `task\dispatch_event_task` | On demand (queued by observer) |

---

## Scheduled Task: `consume_responses_task`

**Class:** `\local_integrationhub\task\consume_responses_task`
**File:** `classes/task/consume_responses_task.php`
**Schedule:** Every minute (`* * * * *`)

### Purpose

Consumes inbound messages from AMQP response queues. This enables a request-response pattern over RabbitMQ: MIH publishes a message, the external service processes it and publishes a response to a reply queue, and this task picks up the response.

### When It Runs

Every time Moodle cron executes (typically every minute).

### What It Does

1. Queries `local_integrationhub_svc` for all enabled AMQP services that have a `response_queue` configured
2. For each such service:
   - Connects to RabbitMQ using `amqp_helper::create_connection()`
   - Consumes all pending messages from the `response_queue`
   - For each message:
     - Parses the JSON body
     - Logs the inbound message to `local_integrationhub_log` (with `direction = 'inbound'`)
     - Acknowledges the message (`basic_ack`)
   - Closes the connection

### Configuration

To enable response consumption for a service:
1. Edit the service in the dashboard
2. Set the **Response Queue** field to the name of the RabbitMQ queue where responses will be published

### Task Definition (`db/tasks.php`)

```php
$tasks = [
    [
        'classname' => '\local_integrationhub\task\consume_responses_task',
        'blocking'  => 0,
        'minute'    => '*',
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
```

### Changing the Schedule

Via the Moodle UI:
1. Go to **Site Administration > Server > Scheduled Tasks**
2. Find **Consume AMQP Responses**
3. Click the edit icon and change the schedule

Via CLI:
```bash
php admin/cli/scheduled_task.php \
    --execute='\local_integrationhub\task\consume_responses_task'
```

---

## Adhoc Task: `dispatch_event_task`

**Class:** `\local_integrationhub\task\dispatch_event_task`
**File:** `classes/task/dispatch_event_task.php`
**Type:** Adhoc (queued on demand)

### Purpose

Processes one queued event and dispatches it to the configured external service. One task is created per matching rule per event occurrence.

### When It Runs

As soon as Moodle cron runs after the task is queued (typically within 1 minute of the event firing).

### Custom Data Structure

```php
[
    'ruleid'    => int,    // ID of the matching rule
    'eventdata' => array,  // $event->get_data() from the observer
    'attempts'  => int,    // MIH's own attempt counter (starts at 0)
]
```

### Retry Behavior

| Attempt | What Happens |
|---------|-------------|
| 1–4 | On failure: increment `attempts`, rethrow exception → Moodle retries |
| 5 | On failure: call `move_to_dlq()`, return → task completes, no more retries |

Moodle's own retry schedule applies between attempts (typically with increasing delays).

### Monitoring Adhoc Tasks

Check the adhoc task queue:

```bash
php admin/cli/adhoc_task.php --list
```

Run all pending adhoc tasks immediately:

```bash
php admin/cli/adhoc_task.php --execute
```

Run a specific task class:

```bash
php admin/cli/adhoc_task.php \
    --execute='\local_integrationhub\task\dispatch_event_task'
```

### Debugging Task Failures

Failed tasks appear in Moodle's task log. To view:

1. Go to **Site Administration > Server > Task Logs**
2. Filter by class `\local_integrationhub\task\dispatch_event_task`

Or via CLI:

```bash
php admin/cli/adhoc_task.php --showfails
```

The task outputs detailed `mtrace()` messages including:
- The event name and service being called
- The interpolated payload
- The HTTP status and response body
- The error message on failure

---

## Task Interaction with the Gateway

Both tasks use `gateway::instance()->request()` internally. This means:
- Circuit breaker checks apply
- Retry policy applies (within the task's single execution)
- All requests are logged to `local_integrationhub_log`

The task-level retry (up to 5 attempts via Moodle's cron) is separate from the Gateway-level retry (up to `max_retries` within a single `request()` call). In the worst case, a single event dispatch can make `max_retries * 5` total network attempts.

---

## Performance Considerations

- In high-traffic Moodle instances with many event rules, the adhoc task queue can grow large
- Consider using Moodle's parallel task runner for better throughput:
  ```bash
  php admin/cli/adhoc_task.php --execute --parallel=4
  ```
- The `consume_responses_task` holds a RabbitMQ connection open for the duration of its execution — ensure your broker's connection limits are set appropriately
