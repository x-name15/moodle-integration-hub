<h1>
  <img src="pix/icon.png" width="40" height="40">
  Integration Hub for Moodle™
</h1>

A centralized integration layer for Moodle™ that allows connecting platform events to external services without writing boilerplate code.

[![Moodle](https://img.shields.io/badge/Moodle-4.1%2B-orange)](https://moodle.org)  [![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)  [![License](https://img.shields.io/badge/License-GPL%20v3-green)](LICENSE) ![Published on Moodle.org](https://img.shields.io/badge/Published%20on-Moodle.org-green)

---

 # **Officially published on Moodle.org:** [local_integrationhub](https://moodle.org/plugins/local_integrationhub) 🎉

## Overview

**Integration Hub for Moodle™ (MIH)** is a local plugin that provides a centralized gateway for external integrations.  
It manages HTTP communication, authentication, retries, and error logging from a single dashboard.

> This plugin is not affiliated with or endorsed by Moodle Pty Ltd.

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

# 2. Upgrade Moodle
php admin/cli/upgrade.php
```

### External Dependencies

This dependency is not bundled with the plugin and must be installed manually:

```bash
cd local/integrationhub
composer install --no-dev
```

> [!NOTE]
> If Composer dependencies are not installed, AMQP transport will be unavailable but the plugin will continue to function for REST and SOAP integrations.

---

## 🗺️ Future Roadmap

The full roadmap is available here: [https://mih.mrjacket.dev/docs/roadmap](https://mih.mrjacket.dev/docs/roadmap)

Upcoming updates:
- [ ] Webhook Firewall
- [ ] Webhook Ingress (receive events from external services)

*License: GPL v3*

Made with ❤️ by Mr Jacket
