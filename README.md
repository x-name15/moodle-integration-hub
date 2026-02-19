<h1><img src="pix/icon.png" width="40" height="40"> Moodle Integration Hub</h1>

A centralized integration layer for Moodle — connect any Moodle event to any external service without writing boilerplate code.

[![Moodle](https://img.shields.io/badge/Moodle-4.1%2B-orange)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v3-green)](LICENSE)

---

## Overview

**Moodle Integration Hub (MIH)** is a local plugin that acts as a centralized gateway for integrations. It handles HTTP logic, authentication, retries, and error logging, allowing you to configured everything from a dashboard.

**Key Features:**
- **Service Gateway:** Reusable API for plugins.
- **Event Bridge:** Map Moodle events to external webhooks without code.
- **Resilience:** Circuit breakers, exponential backoff retries, and Dead Letter Queue (DLQ).
- **Monitoring:** Real-time dashboard for success rates and latency.
- **Transports:** REST, AMQP (RabbitMQ), SOAP.

---

## 📚 Documentation

> [!IMPORTANT]
> All project specifications, architecture diagrams, and API references are hosted at:
> ### 🔗 [mih.mrjacket.dev](https://mih.mrjacket.dev)

| Language | Status | Link |
| :--- | :--- | :--- |
| **English** 🇬🇧 | ![Documentation](https://img.shields.io/badge/docs-latest-blue) | [Read here](https://mih.mrjacket.dev/en) |
| **Español** 🇪🇸 | ![Documentación](https://img.shields.io/badge/docs-actualizado-green) | [Leer aquí](https://mih.mrjacket.dev/es) |

---

## Quick Start

### Installation

```bash
# 1. Install plugin
cp -r integrationhub /path/to/moodle/local/

# 2. Install dependencies (optional, for AMQP)
cd /path/to/moodle/local/integrationhub && composer install

# 3. Upgrade Moodle
php admin/cli/upgrade.php
```

### 🗺️ Future Roadmap?

- [ ] Better stability
- [ ] Webhook ingress (receive events from external services)
- [ ] Kafka support
- [ ] GraphQL support
- [ ] Advanced retry policies (jitter, custom strategies)

---
*License: GPL v3*

Made with ❤️ by Mr Jacket
