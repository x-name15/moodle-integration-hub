# Installation and Configuration

This document covers everything needed to install MIH in a Moodle environment and configure it for first use.

---

## Requirements

| Requirement | Minimum Version | Notes |
|-------------|----------------|-------|
| Moodle | 4.1 | Tested up to 4.5 |
| PHP | 8.0 | 8.1+ recommended |
| MySQL / MariaDB | 5.7 / 10.4 | PostgreSQL also supported |
| `php-amqplib` | 3.x | **Only required for AMQP/RabbitMQ support** |
| RabbitMQ | 3.8+ | Only if using AMQP transport |

### PHP Extensions Required

- `curl` — for HTTP transport
- `soap` — for SOAP transport (usually bundled with PHP)
- `json` — always available in PHP 8+
- `openssl` — for AMQPS (SSL) connections

---

## Installation Steps

### Step 1: Copy the Plugin

```bash
cp -r integrationhub /path/to/moodle/local/
```

Or clone directly into the Moodle directory:

```bash
cd /path/to/moodle/local
git clone https://github.com/your-org/integrationhub.git integrationhub
```

### Step 2: Install Composer Dependencies (AMQP only)

If you plan to use the AMQP transport (RabbitMQ), install the PHP AMQP library:

```bash
cd /path/to/moodle/local/integrationhub
composer require php-amqplib/php-amqplib
```

> **Note:** If you are not using AMQP, skip this step. The HTTP and SOAP transports have no Composer dependencies.

### Step 3: Run Moodle Upgrade

Via CLI (recommended for production):

```bash
php /path/to/moodle/admin/cli/upgrade.php
```

Or via the web interface:

1. Log in as Site Administrator
2. Navigate to **Site Administration > Notifications**
3. Follow the upgrade prompts

### Step 4: Verify Database Tables

After the upgrade, confirm the following tables were created:

```sql
SHOW TABLES LIKE 'local_integrationhub%';
```

Expected output:
```
local_integrationhub_cb
local_integrationhub_dlq
local_integrationhub_log
local_integrationhub_rules
local_integrationhub_svc
```

### Step 5: Verify Scheduled Tasks

Confirm the scheduled task is registered:

```bash
php admin/cli/scheduled_task.php --list | grep integrationhub
```

Expected:
```
\local_integrationhub\task\consume_responses_task   Every minute
```

---

## Plugin Settings

Navigate to **Site Administration > Server > Integration Hub** (or `admin/settings.php?section=local_integrationhub`).

| Setting | Key | Default | Description |
|---------|-----|---------|-------------|
| Max log entries | `max_log_entries` | `500` | Maximum rows in `local_integrationhub_log` before auto-purge. Set to `0` to disable purging. |

---

## Permissions Setup

By default, the `manage` capability is assigned to the `manager` and `admin` roles. The `view` capability is assigned to `manager`, `admin`, and `editingteacher`.

To customize:

1. Go to **Site Administration > Users > Permissions > Define Roles**
2. Edit the desired role
3. Search for `local/integrationhub`
4. Assign `local/integrationhub:manage` and/or `local/integrationhub:view`

---

## Moodle Cron Configuration

The Event Bridge relies on Moodle's cron system to process adhoc tasks. Ensure cron is running at least every minute:

```bash
# Recommended crontab entry
* * * * * /usr/bin/php /path/to/moodle/admin/cli/cron.php > /dev/null 2>&1
```

Or use the Moodle task runner for better performance:

```bash
# Task runner (parallel processing)
php admin/cli/adhoc_task.php --execute
```

---

## Upgrading

When upgrading MIH to a new version:

1. Replace the plugin files
2. Run `php admin/cli/upgrade.php`
3. Check `db/upgrade.php` for any manual migration steps noted in the changelog

---

## Uninstalling

To completely remove MIH:

1. Go to **Site Administration > Plugins > Plugins Overview**
2. Find **Integration Hub** under Local Plugins
3. Click **Uninstall**

This will:
- Drop all `local_integrationhub_*` tables
- Remove all plugin settings
- Unregister the event observer and scheduled tasks

> **Warning:** All service configurations, rules, and logs will be permanently deleted.

---

## Troubleshooting Installation

### Tables not created

If the tables are missing after upgrade, run:

```bash
php admin/cli/upgrade.php --non-interactive
```

Check the Moodle error log for XMLDB errors.

### AMQP class not found

If you see `Class 'PhpAmqpLib\Connection\AMQPStreamConnection' not found`:

```bash
cd /path/to/moodle/local/integrationhub
composer install
```

Ensure `vendor/autoload.php` exists.

### Observer not firing

Check that the event observer is registered:

```bash
php -r "
define('CLI_SCRIPT', true);
require('/path/to/moodle/config.php');
\$observers = \core\event\manager::get_all_observers();
var_dump(array_key_exists('\core\event\base', \$observers));
"
```

If `false`, purge Moodle caches:

```bash
php admin/cli/purge_caches.php
```
