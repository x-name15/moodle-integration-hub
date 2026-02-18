# Roles y Permisos

MIH define dos capacidades que controlan el acceso a las funcionalidades del plugin.

---

## Capacidades

### `local/integrationhub:manage`

**Propósito:** Acceso administrativo completo — crear, editar y eliminar servicios y reglas.

**Roles por defecto:**
- Administrador del Sitio (siempre tiene esta capacidad)
- Manager

**Lo que controla:**
- Ver los botones "Agregar Servicio" y "Agregar Regla"
- Enviar los formularios de servicio y regla
- Editar y eliminar servicios y reglas existentes
- Resetear circuit breakers
- Reenviar y eliminar entradas de la DLQ
- Acceder al endpoint AJAX de vista previa de payload

---

### `local/integrationhub:view`

**Propósito:** Acceso de solo lectura al dashboard, logs y estado de servicios.

**Roles por defecto:**
- Administrador del Sitio
- Manager
- Profesor con permisos de edición (opcional, según configuración)

**Lo que controla:**
- Acceder a `/local/integrationhub/index.php` (dashboard)
- Acceder a `/local/integrationhub/rules.php` (lista de reglas, solo lectura)
- Acceder a `/local/integrationhub/logs.php` (visor de logs)
- Acceder a `/local/integrationhub/queue.php` (visor DLQ, solo lectura)
- Acceder a `/local/integrationhub/events.php` (eventos enviados)

---

## Definición de Capacidades

Desde `db/access.php`:

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

## Banderas de Riesgo

La capacidad `manage` lleva dos banderas de riesgo:

| Bandera | Significado |
|---------|-------------|
| `RISK_CONFIG` | Puede cambiar la configuración del sistema (URLs de servicios, tokens) |
| `RISK_DATALOSS` | Puede eliminar servicios, reglas y datos de log |

---

## Asignar Capacidades

### Via la UI

1. Ve a **Administración del Sitio > Usuarios > Permisos > Definir Roles**
2. Edita el rol deseado (o crea uno nuevo)
3. Busca `integrationhub`
4. Establece `local/integrationhub:manage` y/o `local/integrationhub:view` en **Permitir**

### Via CLI

```bash
php admin/cli/assign_capability.php \
    --capability=local/integrationhub:view \
    --roleid=5 \
    --contextid=1
```

---

## Permisos de la API Gateway

Cuando otros plugins llaman a `gateway->request()` desde PHP, **no se realiza ninguna verificación de capacidad de MIH**. El Gateway es una API PHP del lado del servidor — es responsabilidad del plugin llamante asegurarse de que la operación está autorizada.

---

## Verificar Capacidades en Código

```php
$context = context_system::instance();

// Verificar manage
if (has_capability('local/integrationhub:manage', $context)) {
    // Mostrar controles de administración
}

// Requerir view (necesario para acceder a cualquier página)
require_capability('local/integrationhub:view', $context);

// Requerir manage (necesario para operaciones de escritura)
if ($canmanage && $action === 'guardar') {
    // Procesar envío del formulario
}
```
