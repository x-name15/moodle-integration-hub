# Estructura de Archivos

Listado completo y anotado del directorio y archivos del plugin MIH.

---

## Árbol de Directorios

```
local/integrationhub/
│
├── amd/                              # Módulos JavaScript AMD
│   ├── src/                          # Archivos fuente (editar estos)
│   │   ├── dashboard.js              # Dashboard: gráficas, formulario de servicio, reset de circuito
│   │   ├── rules.js                  # Reglas: toggle de formulario, detección de tipo de servicio
│   │   └── queue.js                  # Monitor de cola: tabla DLQ, acciones de reenvío/eliminación
│   └── build/                        # Salida minificada (generada por grunt amd)
│       ├── dashboard.min.js
│       ├── rules.min.js
│       └── queue.min.js
│
├── assets/
│   └── min/
│       └── chart.umd.min.js          # Chart.js (hospedado localmente, evita dependencia de CDN)
│
├── classes/                          # Clases PHP (autoloaded PSR-4 por Moodle)
│   │
│   ├── event/
│   │   ├── observer.php              # Listener universal de eventos (captura todos los eventos de Moodle)
│   │   └── webhook_received.php      # Evento personalizado disparado al recibir un webhook entrante
│   │
│   ├── service/
│   │   ├── circuit_breaker.php       # Gestión de estado CLOSED/OPEN/HALFOPEN
│   │   ├── registry.php              # CRUD de servicios (tabla local_integrationhub_svc)
│   │   └── retry_policy.php          # Lógica de reintentos con backoff exponencial
│   │
│   ├── task/
│   │   ├── consume_responses_task.php # Programada: consumir colas de respuesta AMQP
│   │   ├── dispatch_event_task.php    # Adhoc: despachar un evento a un servicio
│   │   └── queue_manager.php          # Utilidades compartidas de cola/DLQ
│   │
│   ├── transport/
│   │   ├── contract.php              # Interfaz: todos los drivers deben implementar execute()
│   │   ├── http.php                  # Driver REST/HTTP (cURL)
│   │   ├── amqp.php                  # Driver RabbitMQ (php-amqplib)
│   │   ├── amqp_helper.php           # Factory de conexiones AMQP (plain + SSL)
│   │   ├── soap.php                  # Driver SOAP (PHP SoapClient)
│   │   └── transport_utils.php       # Trait: success_result() y error_result()
│   │
│   ├── gateway.php                   # Orquestador principal (Singleton). API pública.
│   ├── gateway_response.php          # Value object de respuesta inmutable
│   └── webhook_handler.php           # Maneja peticiones HTTP de webhooks entrantes
│
├── db/                               # Definiciones de base de datos y hooks de Moodle
│   ├── access.php                    # Definiciones de capacidades (manage, view)
│   ├── caches.php                    # Definiciones de caché (event_dedupe)
│   ├── events.php                    # Registro del observer de eventos
│   ├── install.xml                   # Esquema XMLDB (5 tablas)
│   ├── tasks.php                     # Definiciones de tareas programadas
│   └── upgrade.php                   # Script de actualización de DB (migraciones de versión)
│
├── docs/                             # Documentación del plugin
│   ├── README.md                     # Índice de documentación
│   ├── documento_maestro.md          # Referencia maestra en un solo archivo (inglés)
│   ├── en/                           # Documentación en inglés (archivos individuales)
│   │   ├── 01-overview.md
│   │   ├── 02-architecture.md
│   │   ├── 03-installation.md
│   │   ├── 04-admin-guide.md
│   │   ├── 05-gateway-api.md
│   │   ├── 06-event-bridge.md
│   │   ├── 07-data-flow.md
│   │   ├── 08-resilience.md
│   │   ├── 09-transports.md
│   │   ├── 10-database.md
│   │   ├── 11-class-reference.md
│   │   ├── 12-ajax.md
│   │   ├── 13-permissions.md
│   │   ├── 14-tasks.md
│   │   └── 15-file-structure.md
│   └── es/                           # Documentación en español (archivos individuales)
│       ├── 01-descripcion-general.md
│       ├── 02-arquitectura.md
│       ├── 03-instalacion.md
│       ├── 04-guia-administrador.md
│       ├── 05-api-gateway.md
│       ├── 06-event-bridge.md
│       ├── 07-flujo-de-datos.md
│       ├── 08-resiliencia.md
│       ├── 09-transportes.md
│       ├── 10-base-de-datos.md
│       ├── 11-referencia-clases.md
│       ├── 12-ajax.md
│       ├── 13-permisos.md
│       ├── 14-tareas.md
│       └── 15-estructura-archivos.md
│
├── lang/                             # Cadenas de idioma
│   ├── en/
│   │   └── local_integrationhub.php  # Cadenas en inglés
│   └── es/
│       └── local_integrationhub.php  # Cadenas en español
│
├── vendor/                           # Dependencias de Composer (no se commitean a git)
│   └── php-amqplib/                  # Librería AMQP (solo si se usa RabbitMQ)
│
├── ajax.php                          # Endpoint AJAX interno (preview_payload)
├── composer.json                     # Configuración de Composer
├── composer.lock                     # Archivo lock de Composer
├── events.php                        # Página de log de eventos enviados
├── index.php                         # Dashboard principal (pestaña de servicios)
├── logs.php                          # Visor de log de peticiones
├── queue.php                         # Visor de Dead Letter Queue
├── README.md                         # README de GitHub
├── rules.php                         # Gestión de reglas del Event Bridge
├── settings.php                      # Página de configuración del plugin
├── version.php                       # Versión del plugin y requisitos
└── webhook.php                       # Endpoint receptor de webhooks entrantes
```

---

## Archivos Clave Explicados

### `version.php`

Define la versión del plugin, nombre del componente y versión mínima de Moodle:

```php
$plugin->version   = 2026021800;
$plugin->requires  = 2023042400; // Moodle 4.2
$plugin->component = 'local_integrationhub';
```

### `db/install.xml`

El archivo de esquema XMLDB. Define las cinco tablas con sus columnas, tipos, índices y restricciones. Procesado por Moodle durante la instalación.

### `db/events.php`

Registra el observer universal de eventos:

```php
$observers = [
    [
        'eventname' => '\core\event\base',
        'callback'  => '\local_integrationhub\event\observer::handle_event',
    ],
];
```

---

## Lo que NO se Commitea a Git

| Ruta | Razón |
|------|-------|
| `vendor/` | Dependencias de Composer — ejecutar `composer install` |
| `amd/build/` | Generado por `grunt amd` — no editar manualmente |
| `*.log` | Archivos de log |

---

## Agregar Nuevos Archivos

### Nueva Clase PHP

Colócala en el subdirectorio apropiado de `classes/`. El autoloader PSR-4 de Moodle la encontrará automáticamente basándose en el namespace:

| Namespace | Directorio |
|-----------|-----------|
| `local_integrationhub\` | `classes/` |
| `local_integrationhub\service\` | `classes/service/` |
| `local_integrationhub\transport\` | `classes/transport/` |
| `local_integrationhub\task\` | `classes/task/` |
| `local_integrationhub\event\` | `classes/event/` |

### Nuevo Módulo AMD

1. Crea `amd/src/mimodulo.js`
2. Ejecuta `grunt amd` para construir `amd/build/mimodulo.min.js`
3. Cárgalo desde PHP: `$PAGE->requires->js_call_amd('local_integrationhub/mimodulo', 'init', [$data])`

### Nueva Página

1. Crea `mipagina.php` en la raíz del plugin
2. Agrega un enlace de pestaña en la sección de navegación de las páginas existentes
3. Agrega cadenas de idioma en `lang/en/local_integrationhub.php` y `lang/es/local_integrationhub.php`
