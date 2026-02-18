# Internal AJAX Endpoint

MIH exposes a single internal AJAX endpoint at `/local/integrationhub/ajax.php`. This endpoint is used by the plugin's own JavaScript UI — it is not a public API.

---

## Endpoint

```
GET|POST /local/integrationhub/ajax.php
```

### Authentication

All requests require:
- An active Moodle session (`require_login()`)
- The `local/integrationhub:manage` capability
- A valid `sesskey` parameter (CSRF protection)

---

## Actions

### `action=preview_payload`

Previews a payload template with mock event data. Used by the "Preview Payload" button in the rule form.

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Must be `preview_payload` |
| `template` | string | Yes | JSON template with `{{variable}}` placeholders |
| `eventname` | string | No | Event name for context (used in mock data) |
| `sesskey` | string | Yes | Moodle session key |

#### Mock Data Used for Preview

```php
$mockdata = [
    'eventname'    => $eventname ?: '\core\event\user_created',
    'userid'       => 5,
    'objectid'     => 42,
    'courseid'     => 10,
    'contextid'    => 1,
    'contextlevel' => 50,
    'timecreated'  => time(),
    'ip'           => '192.168.1.100',
    'crud'         => 'c',
    'edulevel'     => 2,
];
```

#### Success Response

```json
{
  "success": true,
  "payload": {
    "event": "\\core\\event\\user_created",
    "user_id": 5,
    "timestamp": 1708258939
  },
  "raw": "{\"event\": \"\\\\core\\\\event\\\\user_created\", \"user_id\": 5, \"timestamp\": 1708258939}"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | bool | Always `true` for this response |
| `payload` | object | The decoded JSON payload (for display) |
| `raw` | string | The raw JSON string (for copy-paste) |

#### Error Response (Invalid JSON Template)

```json
{
  "success": false,
  "error": "Syntax error",
  "raw": "{\"event\": \"{{eventname}\""
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | bool | Always `false` for this response |
| `error` | string | JSON error description |
| `raw` | string | The raw (invalid) template string |

---

## JavaScript Usage

The AJAX endpoint is called from `amd/src/rules.js`:

```javascript
// In rules.js
document.getElementById('ih-btn-preview').addEventListener('click', function() {
    const template = document.getElementById('ih-template').value;
    const eventname = document.getElementById('ih-eventname').value;

    fetch(M.cfg.wwwroot + '/local/integrationhub/ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'preview_payload',
            template: template,
            eventname: eventname,
            sesskey: M.cfg.sesskey,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Show formatted payload in modal
        } else {
            // Show error message
        }
    });
});
```

---

## Security Notes

- The endpoint validates `sesskey` on every request to prevent CSRF attacks
- The `require_capability('local/integrationhub:manage', ...)` check ensures only administrators can use it
- Template preview uses mock data only — no real database queries or external calls are made
- The endpoint does not accept or process any data that modifies the database

---

## Extending the Endpoint

To add a new AJAX action, add a new `case` to the switch in `ajax.php`:

```php
switch ($action) {
    case 'preview_payload':
        // ... existing code
        break;

    case 'my_new_action':
        require_sesskey();
        require_capability('local/integrationhub:manage', $context);
        // ... your logic
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
```
