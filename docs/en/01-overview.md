# Overview

**Moodle Integration Hub (MIH)** is a local Moodle plugin that provides a centralized, production-grade integration layer between Moodle and any external service — REST APIs, message brokers (RabbitMQ), or SOAP web services.

---

## The Problem

Modern Moodle deployments are rarely standalone. They connect to external systems: gamification engines, analytics platforms, notification services, ERP systems, SIS integrations, and more. Without a centralized solution, this creates a pattern of duplicated, fragile code:

- Every plugin that needs to call an external service implements its own HTTP logic
- Authentication tokens are scattered across multiple `settings.php` files
- Retry logic and timeout handling are inconsistent or missing entirely
- There is no central place to monitor what is happening across all integrations
- When an external service goes down, there is no protection — Moodle requests pile up and fail silently
- Adding a new integration requires writing PHP code, even for simple event-to-webhook mappings

This is the **integration sprawl** problem. MIH solves it.

---

## The Solution

MIH provides two complementary systems:

### 1. Service Gateway

A singleton PHP class (`gateway`) that any Moodle plugin can call to make HTTP or AMQP requests to registered external services. The Gateway handles:

- **Service resolution** — looks up the service configuration from the database by name
- **Authentication** — automatically applies Bearer tokens or API keys
- **Circuit breaking** — refuses to call services that are known to be down
- **Retry with backoff** — retries failed requests with exponential delays
- **Logging** — records every request, response, and error to a central log table
- **Response wrapping** — returns a consistent `gateway_response` object regardless of transport

### 2. Event Bridge

A no-code system for mapping Moodle events to external service calls. Administrators configure rules in the dashboard — no PHP required. The Event Bridge handles:

- **Universal event listening** — a single observer catches every event in Moodle (core and third-party)
- **Rule matching** — checks which rules apply to each event
- **Deduplication** — prevents the same event from being processed twice
- **Async dispatch** — queues work as Moodle adhoc tasks so user actions are never blocked
- **Template interpolation** — builds JSON payloads from event data using `{{variable}}` syntax
- **Dead Letter Queue** — stores permanently failed events for manual review and replay

---

## Core Capabilities

| Capability | Description |
|------------|-------------|
| **Service Gateway** | Reusable PHP API for HTTP/AMQP calls from any plugin |
| **Event Bridge** | Automatically react to any Moodle event without writing PHP code |
| **Circuit Breaker** | Prevents cascading failures when external services go down |
| **Retry with Exponential Backoff** | Automatic retries with configurable delays |
| **Dead Letter Queue (DLQ)** | Failed events stored for review and replay |
| **Monitoring Dashboard** | Real-time charts for success rates and latency trends |
| **Multi-transport** | REST/HTTP, AMQP (RabbitMQ), and SOAP support |
| **Multilingual UI** | Full English and Spanish support |
| **Auto-purging Logs** | Log table is automatically pruned to prevent database bloat |

---

## Design Philosophy

MIH was built with the following principles:

- **Centralization over duplication** — one place to configure, monitor, and debug all integrations
- **Resilience by default** — circuit breakers and retries are always on, not opt-in
- **Non-blocking** — the Event Bridge uses async tasks so user-facing actions are never delayed by integration failures
- **Zero-code integrations** — common event-to-webhook patterns require no PHP
- **Moodle-native** — uses Moodle's own DB layer, task system, cache API, and output renderer; no external frameworks

---

## What MIH Is Not

- MIH is **not** a replacement for Moodle's web services (REST/SOAP API for external systems to call Moodle)
- MIH is **not** a full ESB (Enterprise Service Bus) — it is focused on outbound integrations from Moodle
- MIH is **not** a message consumer by default — the AMQP transport publishes messages; consuming responses is handled by a separate scheduled task

---

## Next Steps

- [Architecture](02-architecture.md) — understand how the components fit together
- [Installation](03-installation.md) — get MIH running in your environment
- [Admin Guide](04-admin-guide.md) — configure services and rules
- [Gateway API](05-gateway-api.md) — integrate MIH into your own plugin
