# Endpoint AJAX Interno

MIH expone un único endpoint AJAX interno en `/local/integrationhub/ajax.php`. Este endpoint es usado por la propia UI JavaScript del plugin — no es una API pública.

---

## Endpoint

```
GET|POST /local/integrationhub/ajax.php
```

### Autenticación

Todas las peticiones requieren:
- Una sesión activa de Moodle (`require_login()`)
- La capacidad `local/integrationhub:manage`
- Un parámetro `sesskey` válido (protección CSRF)

---

## Acciones

### `action=preview_payload`

Previsualiza un template de payload con datos de evento de prueba. Usado por el botón "Vista Previa del Payload" en el formulario de reglas.

#### Parámetros de Petición

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `action` | string | Sí | Debe ser `preview_payload` |
| `template` | string | Sí | Template JSON con placeholders `{{variable}}` |
| `eventname` | string | No | Nombre del evento para contexto (usado en datos de prueba) |
| `sesskey` | string | Sí | Clave de sesión de Moodle |

#### Datos de Prueba Usados para Vista Previa

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

#### Respuesta Exitosa

```json
{
  "success": true,
  "payload": {
    "evento": "\\core\\event\\user_created",
    "id_usuario": 5,
    "timestamp": 1708258939
  },
  "raw": "{\"evento\": \"\\\\core\\\\event\\\\user_created\", \"id_usuario\": 5, \"timestamp\": 1708258939}"
}
```

#### Respuesta de Error (Template JSON Inválido)

```json
{
  "success": false,
  "error": "Syntax error",
  "raw": "{\"evento\": \"{{eventname}\""
}
```

---

## Notas de Seguridad

- El endpoint valida `sesskey` en cada petición para prevenir ataques CSRF
- La verificación `require_capability('local/integrationhub:manage', ...)` asegura que solo los administradores puedan usarlo
- La vista previa del template usa solo datos de prueba — no se realizan consultas reales a la base de datos ni llamadas externas
- El endpoint no acepta ni procesa datos que modifiquen la base de datos

---

## Extender el Endpoint

Para agregar una nueva acción AJAX, añade un nuevo `case` al switch en `ajax.php`:

```php
switch ($action) {
    case 'preview_payload':
        // ... código existente
        break;

    case 'mi_nueva_accion':
        require_sesskey();
        require_capability('local/integrationhub:manage', $context);
        // ... tu lógica
        echo json_encode(['success' => true, 'data' => $resultado]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción desconocida']);
}
```
