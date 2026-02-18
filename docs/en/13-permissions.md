# Roles and Permissions

MIH defines two capabilities that control access to the plugin's features.

---

## Capabilities

### `local/integrationhub:manage`

**Purpose:** Full administrative access — create, edit, and delete services and rules.

**Default roles:**
- Site Administrator (always has this)
- Manager

**What it controls:**
- Seeing the "Add Service" and "Add Rule" buttons
- Submitting the service and rule forms
- Editing and deleting existing services and rules
- Resetting circuit breakers
- Replaying and deleting DLQ entries
- Accessing the payload preview AJAX endpoint

---

### `local/integrationhub:view`

**Purpose:** Read-only access to the dashboard, logs, and service status.

**Default roles:**
- Site Administrator
- Manager
- Editing Teacher (optional, depends on your configuration)

**What it controls:**
- Accessing `/local/integrationhub/index.php` (dashboard)
- Accessing `/local/integrationhub/rules.php` (rules list, read-only)
- Accessing `/local/integrationhub/logs.php` (log viewer)
- Accessing `/local/integrationhub/queue.php` (DLQ viewer, read-only)
- Accessing `/local/integrationhub/events.php` (sent events)

---

## Capability Definitions

From `db/access.php`:

```php
$capabilities = [
    'local/integrationhub:manage' => [
        'riskbitmask'  => RISK_CONFIG | RISK_DATALOSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/integrationhub:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
    ],
];
```

---

## Risk Flags

The `manage` capability carries two risk flags:

| Flag | Meaning |
|------|---------|
| `RISK_CONFIG` | Can change system configuration (service URLs, tokens) |
| `RISK_DATALOSS` | Can delete services, rules, and log data |

These flags ensure that Moodle's capability review tools flag this capability as high-risk, which is appropriate — an administrator with `manage` can configure integrations that send Moodle data to external systems.

---

## Assigning Capabilities

### Via the UI

1. Go to **Site Administration > Users > Permissions > Define Roles**
2. Edit the desired role (or create a new one)
3. Search for `integrationhub`
4. Set `local/integrationhub:manage` and/or `local/integrationhub:view` to **Allow**

### Via CLI

```bash
php admin/cli/assign_capability.php \
    --capability=local/integrationhub:view \
    --roleid=5 \
    --contextid=1
```

---

## Gateway API Permissions

When other plugins call `gateway->request()` from PHP, **no MIH capability check is performed**. The Gateway is a server-side PHP API — it is the calling plugin's responsibility to ensure the operation is authorized.

For example, if your plugin calls the Gateway from a scheduled task, no user context is involved and no capability check is needed. If you call it from a user-facing action, check the appropriate capability in your plugin before calling the Gateway.

---

## Context

Both capabilities are defined at `CONTEXT_SYSTEM` level. MIH is a site-wide plugin — there is no per-course or per-category access control.

---

## Checking Capabilities in Code

```php
$context = context_system::instance();

// Check manage
if (has_capability('local/integrationhub:manage', $context)) {
    // Show admin controls
}

// Check view (required to access any page)
require_capability('local/integrationhub:view', $context);

// Check manage (required for write operations)
if ($canmanage && $action === 'save') {
    // Process form submission
}
```
