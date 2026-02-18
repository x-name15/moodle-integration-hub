# Administrator Guide

This guide covers the day-to-day administration of Moodle Integration Hub: registering services, creating event rules, monitoring the dashboard, and managing the Dead Letter Queue.

---

## Accessing the Plugin

Navigate to `/local/integrationhub/index.php` or use the **Services** tab in the plugin navigation.

The plugin has four main sections:

| Tab | URL | Description |
|-----|-----|-------------|
| **Services** | `/local/integrationhub/index.php` | Register and manage external services |
| **Rules** | `/local/integrationhub/rules.php` | Create Event Bridge rules |
| **Queue** | `/local/integrationhub/queue.php` | Monitor the Dead Letter Queue |
| **Events** | `/local/integrationhub/events.php` | View sent event log |

---

## Managing Services

### Adding a REST/HTTP Service

1. Go to the **Services** tab
2. Click **Add Service**
3. Fill in the form:

#### Basic Settings

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Unique slug. No spaces. Used in PHP calls: `gateway->request('this-name', ...)` | `notification-api` |
| **Type** | Transport protocol | `REST` |
| **Base URL** | Root URL. Endpoint paths are appended to this | `https://api.example.com/v1` |
| **Enabled** | Toggle to activate/deactivate without deleting | checked |

#### Authentication

| Field | Description | Example |
|-------|-------------|---------|
| **Auth Type** | `Bearer` sends `Authorization: Bearer {token}`. `API Key` sends `X-API-Key: {token}` | `Bearer` |
| **Token / API Key** | The credential value | `eyJhbGci...` |

#### Resilience Settings

| Field | Description | Recommended |
|-------|-------------|-------------|
| **Timeout (s)** | Seconds before the request is cancelled | `5` |
| **Max Retries** | Additional attempts after the first failure | `3` |
| **Initial Backoff (s)** | Seconds before the first retry. Doubles each attempt | `1` |
| **CB Failure Threshold** | Consecutive failures before the circuit opens | `5` |
| **CB Cooldown (s)** | Seconds before the circuit attempts recovery (HALFOPEN) | `30` |

#### Constructed Request

For a service with `base_url = https://api.example.com/v1` and a rule with `endpoint = /users`:

```
POST https://api.example.com/v1/users
Authorization: Bearer eyJhbGci...
Content-Type: application/json

{"userid": 5, "action": "created"}
```

---

### Adding an AMQP Service (RabbitMQ)

When **Type = AMQP**, the form shows a **Connection Builder** instead of a plain URL field.

#### Connection Builder Fields

| Field | Description | Default |
|-------|-------------|---------|
| **Host** | RabbitMQ broker hostname or IP | `localhost` |
| **Port** | `5672` for plain AMQP, `5671` for AMQPS (SSL) | `5672` |
| **User** | RabbitMQ username | `guest` |
| **Password** | RabbitMQ password | `guest` |
| **Virtual Host** | RabbitMQ vhost | `/` |
| **Exchange** | Target exchange name. Leave empty for the default exchange | *(empty)* |
| **Routing Key** | Default routing key for published messages | `events.moodle` |
| **Queue to Declare** | Queue to auto-declare when connecting. Useful for development | `moodle_events` |
| **Dead Letter Queue** | Queue name for messages that cannot be delivered | `moodle_dlq` |

The connection URL is built automatically:

```
amqp://user:password@host:5672/vhost?exchange=X&routing_key=Y&queue_declare=Z&dlq=DLQ
```

> **Tip:** For production, use `amqps://` (port 5671) with SSL enabled on your RabbitMQ broker.

#### Routing Key Override

When calling the Gateway from PHP or from an Event Bridge rule, the `endpoint` parameter acts as a **routing key override**:

```php
// Uses the service's default routing key
$gateway->request('rabbitmq-prod', '/', $payload);

// Overrides with a specific routing key
$gateway->request('rabbitmq-prod', 'events.user.created', $payload);
```

---

### Adding a SOAP Service

| Field | Description | Example |
|-------|-------------|---------|
| **Base URL** | The WSDL URL | `https://legacy.example.com/service?wsdl` |
| **Type** | `SOAP` | `SOAP` |

When calling via Gateway or Event Bridge, the `endpoint` field is the **SOAP method name**:

```php
$gateway->request('legacy-soap', 'CreateUser', ['name' => 'John', 'email' => 'j@example.com']);
```

---

### Editing and Deleting Services

- Click the **pencil icon** on any service row to edit
- Click the **trash icon** to delete (requires confirmation)
- Deleting a service also deletes all associated rules, logs, and circuit breaker state

---

## Managing Rules (Event Bridge)

### Adding a Rule

1. Go to the **Rules** tab
2. Click **Add Rule**

#### Rule Fields

| Field | Description | Example |
|-------|-------------|---------|
| **Event** | Full PHP class name of the Moodle event. Use the autocomplete datalist | `\core\event\user_created` |
| **Service** | Target service (only enabled services appear) | `notification-api` |
| **HTTP Method** | For REST services: `POST`, `GET`, `PUT`, `PATCH`, `DELETE` | `POST` |
| **Endpoint** | Path appended to the service's base URL (optional). For AMQP: routing key override | `/webhooks/users` |
| **Payload Template** | JSON template with `{{variable}}` placeholders | See below |
| **Active** | Enable/disable without deleting | checked |

#### Payload Template Syntax

```json
{
  "event": "{{eventname}}",
  "user_id": {{userid}},
  "object_id": {{objectid}},
  "course_id": {{courseid}},
  "timestamp": {{timecreated}},
  "source": "moodle"
}
```

> **Important:** Numeric values (`{{userid}}`, `{{objectid}}`, etc.) should NOT be wrapped in quotes in the template — they will be replaced with raw integers. String values (`{{eventname}}`, `{{ip}}`) should be wrapped in quotes.

#### Preview Payload

Click **Preview Payload** to see the interpolated result with mock data before saving the rule.

---

### Common Event Names

| Event | Description |
|-------|-------------|
| `\core\event\user_created` | A new user account was created |
| `\core\event\user_updated` | A user profile was updated |
| `\core\event\user_deleted` | A user was deleted |
| `\core\event\course_created` | A new course was created |
| `\core\event\course_completed` | A user completed a course |
| `\core\event\user_enrolment_created` | A user was enrolled in a course |
| `\core\event\user_enrolment_deleted` | A user was unenrolled |
| `\core\event\grade_item_updated` | A grade was updated |
| `\core\event\user_loggedin` | A user logged in |
| `\core\event\user_loggedout` | A user logged out |
| `\core\event\badge_awarded` | A badge was awarded to a user |
| `\core\event\message_sent` | A message was sent |

---

## Dashboard Monitoring

The main dashboard (`/local/integrationhub/index.php`) provides:

### Charts

| Chart | Description |
|-------|-------------|
| **Status Distribution** | Pie chart showing the ratio of successful vs. failed requests across all services |
| **Latency Trend** | Line chart showing response times for the last 200 requests |

### Services Table

Each service row shows:

| Column | Description |
|--------|-------------|
| **Name** | Service slug |
| **Type** | REST / AMQP / SOAP |
| **Circuit** | Current circuit state: CLOSED (green), OPEN (red), HALFOPEN (yellow) |
| **Avg Latency** | Average response time over the last 24 hours |
| **Errors (24h)** | Number of failed requests in the last 24 hours |
| **Last Used** | Timestamp of the most recent request |
| **Actions** | Edit, Delete, Reset Circuit |

### Resetting a Circuit

If a service has recovered but its circuit is still OPEN:

1. Click **Reset Circuit** on the service row
2. The circuit transitions to CLOSED and the failure counter resets

To reset all circuits at once, click **Reset All Circuits**.

---

## Dead Letter Queue (DLQ)

When an event fails to dispatch after 5 attempts, it is moved to the DLQ.

Navigate to `/local/integrationhub/queue.php` to:

- View all failed events with their error messages and payloads
- **Replay** individual events (re-queues them as a new adhoc task)
- **Delete** events that are no longer needed

### When Events End Up in the DLQ

- The target service is permanently down and the circuit never recovers
- The payload template produces invalid JSON
- The service was deleted after the rule was created
- A network error persists beyond 5 retry attempts

---

## Log Viewer

Navigate to `/local/integrationhub/logs.php` to view the request log.

The log shows:
- Timestamp
- Service name
- Endpoint called
- HTTP method
- HTTP status code
- Latency (ms)
- Number of attempts
- Success/failure status
- Error message (if failed)

> **Note:** The log is automatically pruned to the configured maximum (default: 500 entries). Older entries are deleted first.
