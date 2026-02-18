# Event Bridge — Automatic Event Dispatch

The Event Bridge is MIH's no-code integration system. It lets administrators map any Moodle event to any external service call — without writing PHP.

---

## How It Works

At a high level:

1. A user does something in Moodle (logs in, completes a course, submits an assignment)
2. Moodle fires an event (a PHP class extending `\core\event\base`)
3. MIH's universal observer catches the event
4. The observer looks up matching active rules in the database
5. For each matching rule, an adhoc task is queued
6. Moodle cron picks up the task and calls the Gateway
7. The Gateway sends the payload to the configured service

---

## The Universal Observer

The observer is registered in `db/events.php` against `\core\event\base` — the base class for **every** event in Moodle:

```php
// db/events.php
$observers = [
    [
        'eventname' => '\core\event\base',
        'callback'  => '\local_integrationhub\event\observer::handle_event',
    ],
];
```

This means MIH catches:
- All Moodle core events (user, course, grade, enrolment, etc.)
- All third-party plugin events
- Any custom events you define in your own plugins

### What the Observer Does

```php
public static function handle_event(\core\event\base $event): void {
    // 1. Get the event class name
    $eventname = get_class($event);

    // 2. Find active rules for this event
    $rules = $DB->get_records('local_integrationhub_rules', [
        'eventname' => $eventname,
        'enabled'   => 1,
    ]);

    if (empty($rules)) {
        return; // No rules — nothing to do
    }

    // 3. Deduplication check
    $signature = sha1($eventname . $event->objectid . $event->userid . $event->crud);
    $cache = \cache::make('local_integrationhub', 'event_dedupe');
    if ($cache->get($signature)) {
        return; // Already processed
    }
    $cache->set($signature, 1);

    // 4. Queue one adhoc task per rule
    foreach ($rules as $rule) {
        $task = new \local_integrationhub\task\dispatch_event_task();
        $task->set_custom_data([
            'ruleid'    => $rule->id,
            'eventdata' => $event->get_data(),
        ]);
        \core\task\manager::queue_adhoc_task($task);
    }
}
```

---

## Payload Templates

Templates define the JSON body sent to the external service. They use `{{variable}}` placeholders that are replaced with values from the event data at dispatch time.

### Basic Template

```json
{
  "event": "{{eventname}}",
  "user_id": {{userid}},
  "timestamp": {{timecreated}}
}
```

### Available Variables

These variables are available in every template, sourced from `$event->get_data()`:

| Variable | Type | Description | Example |
|----------|------|-------------|---------|
| `{{eventname}}` | string | Full event class name | `\core\event\user_created` |
| `{{userid}}` | int | ID of the user who triggered the event | `5` |
| `{{objectid}}` | int | ID of the primary object affected | `42` |
| `{{courseid}}` | int | Course ID (0 if not course-specific) | `10` |
| `{{contextid}}` | int | Moodle context ID | `1` |
| `{{contextlevel}}` | int | Context level (10=system, 50=course, etc.) | `50` |
| `{{timecreated}}` | int | Unix timestamp of the event | `1708258939` |
| `{{ip}}` | string | IP address of the user | `192.168.1.100` |
| `{{crud}}` | string | Operation type: `c`reate, `r`ead, `u`pdate, `d`elete | `c` |
| `{{edulevel}}` | int | Educational level (0=other, 1=teaching, 2=participating) | `2` |

### Type-Aware Replacement

The template engine is type-aware:

- **Integers** (`{{userid}}`, `{{objectid}}`, etc.) are replaced as raw numbers — do not wrap in quotes
- **Strings** (`{{eventname}}`, `{{ip}}`) are JSON-escaped and should be wrapped in quotes
- **Booleans** are replaced as `true` or `false`

```json
{
  "event_class": "{{eventname}}",
  "user_id": {{userid}},
  "object_id": {{objectid}},
  "is_system": false,
  "metadata": {
    "course": {{courseid}},
    "ip": "{{ip}}",
    "time": {{timecreated}}
  }
}
```

### Default Template (No Template Set)

If no payload template is configured, the raw event data array is sent as-is:

```json
{
  "eventname": "\\core\\event\\user_created",
  "userid": 5,
  "objectid": 5,
  "courseid": 0,
  "contextid": 1,
  "timecreated": 1708258939,
  ...
}
```

---

## Deduplication

The observer uses Moodle's application cache to prevent duplicate processing.

**Cache definition** (`db/caches.php`):
```php
$definitions = [
    'event_dedupe' => [
        'mode'      => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'ttl'       => 60, // 60 seconds
    ],
];
```

**Deduplication key:**
```php
$signature = sha1($eventname . $event->objectid . $event->userid . $event->crud);
```

If the same logical event fires twice within 60 seconds (same event class, same object, same user, same operation), only the first occurrence is processed.

---

## Dispatch Task

The `dispatch_event_task` adhoc task handles the actual delivery:

### Execution Flow

```
dispatch_event_task::execute()
    │
    ├── Load rule from DB (check it still exists and is enabled)
    ├── Load service from DB (check it still exists and is enabled)
    │
    ├── Prepare payload:
    │   ├── If template is empty: use raw event data
    │   └── If template exists:
    │       ├── Replace {{variables}} with event data values
    │       ├── Decode as JSON
    │       └── If JSON is invalid: throw exception (task will retry)
    │
    ├── Determine method:
    │   ├── AMQP service: method = 'AMQP'
    │   ├── Rule has http_method: use it
    │   └── Default: 'POST'
    │
    ├── gateway::instance()->request(service, endpoint, payload, method)
    │
    ├── On success: mtrace success message, task completes
    │
    └── On failure:
        ├── Increment attempt counter in custom_data
        ├── If attempts < 5: rethrow (Moodle retries the task)
        └── If attempts >= 5: move_to_dlq(), return (stop retrying)
```

### Retry Behavior

Moodle's adhoc task system has its own retry mechanism. When `dispatch_event_task` rethrows an exception, Moodle will retry the task according to its own schedule (typically with increasing delays).

MIH tracks its own attempt counter in `custom_data` to enforce a maximum of 5 total attempts before giving up and writing to the DLQ.

---

## Dead Letter Queue

When a task reaches 5 failed attempts, the payload is written to `local_integrationhub_dlq`:

```php
protected function move_to_dlq($rule, $payload, $error): void {
    global $DB;
    $dlq = new \stdClass();
    $dlq->eventname     = $rule->eventname;
    $dlq->serviceid     = $rule->serviceid;
    $dlq->payload       = json_encode($payload);
    $dlq->error_message = $error;
    $dlq->timecreated   = time();
    $DB->insert_record('local_integrationhub_dlq', $dlq);
}
```

DLQ entries can be reviewed and replayed from the **Queue** tab in the dashboard.

---

## Practical Examples

### Notify Slack When a User Enrolls

**Service:** `slack-webhook` (REST, POST to Slack Incoming Webhook URL)

**Event:** `\core\event\user_enrolment_created`

**Template:**
```json
{
  "text": "New enrollment: User {{userid}} enrolled in course {{courseid}}",
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*New Enrollment*\nUser ID: {{userid}}\nCourse ID: {{courseid}}\nTime: {{timecreated}}"
      }
    }
  ]
}
```

---

### Publish to RabbitMQ on Course Completion

**Service:** `rabbitmq-prod` (AMQP)

**Event:** `\core\event\course_completed`

**Endpoint (Routing Key):** `lms.events.course.completed`

**Template:**
```json
{
  "event_type": "course_completed",
  "user_id": {{userid}},
  "course_id": {{courseid}},
  "completed_at": {{timecreated}},
  "source": "moodle"
}
```

---

### Sync User to CRM on Profile Update

**Service:** `crm-api` (REST, PUT)

**Event:** `\core\event\user_updated`

**Endpoint:** `/contacts/{{userid}}`

**Template:**
```json
{
  "moodle_id": {{userid}},
  "updated_at": {{timecreated}},
  "source": "moodle_lms"
}
```

---

## Limitations

- Template variables are limited to the flat fields in `$event->get_data()`. Nested data (e.g., `other` array contents) is not directly accessible via `{{variable}}` syntax.
- The observer fires on every Moodle event — in high-traffic systems, ensure your rules are specific to avoid unnecessary DB queries.
- Deduplication is based on a 60-second window. Events that legitimately fire multiple times for different objects within 60 seconds will be correctly processed (the signature includes `objectid`).
