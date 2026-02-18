<h1><img src="pix/icon.png" width="40" height="40"> Moodle Integration Hub</h1>

A centralized integration layer for Moodle — connect any Moodle event to any external service without writing boilerplate code.

[![Moodle](https://img.shields.io/badge/Moodle-4.1%2B-orange)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v3-green)](LICENSE)

---

## What is it?

**Moodle Integration Hub (MIH)** is a local Moodle plugin that acts as a centralized integration gateway between Moodle and external services (REST APIs, RabbitMQ, SOAP web services).

Instead of every plugin implementing its own HTTP logic, token management, retry handling, and error logging — MIH provides all of this in one place, configurable from a dashboard.

## Features

- **Service Gateway** — Reusable PHP API for any plugin to call external services
- **Event Bridge** — Automatically react to any Moodle event without writing PHP code
- **Circuit Breaker** — Prevents cascading failures when external services go down
- **Retry with Exponential Backoff** — Automatic retries with configurable delays
- **Dead Letter Queue** — Failed events are stored for review and replay
- **Monitoring Dashboard** — Real-time charts for success rates and latency trends
- **Multi-transport** — REST/HTTP, AMQP (RabbitMQ), and SOAP support
- **Multilingual** — Full English and Spanish support

## Quick Start

### Installation

```bash
# 1. Place the plugin
cp -r integrationhub /path/to/moodle/local/

# 2. Install AMQP support (optional)
cd /path/to/moodle/local/integrationhub
composer require php-amqplib/php-amqplib

# 3. Run Moodle upgrade
php admin/cli/upgrade.php
```

### Register a Service

1. Go to **Site Administration > Server > Integration Hub**
2. Click **Add Service**
3. Fill in the service name, URL, authentication, and resilience settings

### Create an Event Rule

1. Go to the **Rules** tab
2. Click **Add Rule**
3. Select a Moodle event (e.g., `\core\event\user_created`) and map it to your service
4. Write a JSON payload template using `{{variable}}` placeholders

### Call from Your Plugin

```php
$gateway = \local_integrationhub\gateway::instance();

$response = $gateway->request(
    'my-service-name',   // Service slug from the dashboard
    '/api/v1/users',     // Endpoint path
    ['userid' => $USER->id, 'action' => 'login'],
    'POST'
);

if ($response->is_ok()) {
    $data = $response->json(); // Decoded JSON response
} else {
    echo $response->error;     // Error message
}
```

## Architecture

```
Moodle Event --> Observer --> Adhoc Task Queue --> Gateway --> External Service
                                                      |
Plugin PHP -----------------------------------------> Gateway --> External Service
                                                      |
                                            +---------+---------+
                                       HTTP Driver         AMQP Driver
                                       (REST/cURL)       (RabbitMQ)
```

## Resilience

### Circuit Breaker States

| State | Behavior |
|-------|----------|
| **CLOSED** | Normal operation — requests pass through |
| **OPEN** | Service is down — requests fail immediately (no network call) |
| **HALFOPEN** | Testing recovery — one request is allowed through |

The circuit opens automatically when failures exceed the configured threshold. It resets automatically after the cooldown period, or manually from the dashboard.

### Retry Policy

Retries use exponential backoff (capped at 60s):

- Attempt 1: immediate
- Attempt 2: wait `backoff x 1`s
- Attempt 3: wait `backoff x 2`s
- Attempt 4: wait `backoff x 4`s

## Database Tables

| Table | Purpose |
|-------|---------|
| `local_integrationhub_svc` | Registered external services |
| `local_integrationhub_cb` | Circuit breaker state per service |
| `local_integrationhub_log` | Request log (auto-purged, max 500 entries) |
| `local_integrationhub_rules` | Event Bridge rules |
| `local_integrationhub_dlq` | Dead Letter Queue for failed events |

## Supported Transports

| Transport | Protocol | Use Case |
|-----------|----------|----------|
| **HTTP** | REST/JSON | Standard web APIs |
| **AMQP** | RabbitMQ | Async messaging, event streaming |
| **SOAP** | XML/SOAP | Legacy enterprise systems |

## Permissions

| Capability | Description |
|------------|-------------|
| `local/integrationhub:manage` | Create, edit, delete services and rules |
| `local/integrationhub:view` | View dashboard, logs, and service status |

## Requirements

- Moodle 4.1 or higher
- PHP 8.0 or higher
- `php-amqplib` (only for AMQP/RabbitMQ support)

## Documentation

Full technical documentation is available in [`docs/README.md`](docs/README.md), covering:

- Complete API reference
- Data flow diagrams
- Database schema
- Configuration guide
- Event Bridge template syntax
- Resilience patterns

## License

This plugin is licensed under the [GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.html).
