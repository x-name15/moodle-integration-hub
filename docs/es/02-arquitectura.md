# Arquitectura del Sistema

Este documento describe la arquitectura interna de Moodle Integration Hub, las responsabilidades de cada componente y las decisiones de diseño detrás de ellos.

---

## Vista General de Alto Nivel

MIH está estructurado alrededor de dos caminos de ejecución independientes pero complementarios:

```
+=====================================================================+
|                         MOODLE CORE                                 |
|                                                                     |
|  [Cualquier Evento] ──► [Observer Universal] ──► [Tarea Adhoc]     |
|                                                         │           |
|  [Plugin Externo]   ──────────────────────────────►    │           |
|                                                         │           |
|                                              ┌──────────▼────────┐ |
|                                              │   gateway.php     │ |
|                                              │  (Orquestador)    │ |
|                                              └──────────┬────────┘ |
|                                                         │           |
|                              ┌──────────────────────────┤           |
|                              │                          │           |
|                    ┌─────────▼──────┐        ┌─────────▼──────┐   |
|                    │ circuit_breaker│        │  retry_policy  │   |
|                    │  (Guardián)    │        │  (Resiliencia) │   |
|                    └─────────┬──────┘        └─────────┬──────┘   |
|                              └──────────┬───────────────┘           |
|                                         │                           |
|                    ┌────────────────────┼────────────────────┐     |
|                    │                    │                    │     |
|          ┌─────────▼──────┐  ┌─────────▼──────┐  ┌─────────▼──┐  |
|          │ transport\http │  │ transport\amqp │  │transport\  │  |
|          │  (REST/cURL)   │  │  (RabbitMQ)    │  │  soap      │  |
|          └─────────┬──────┘  └─────────┬──────┘  └─────────┬──┘  |
+=====================================================================+
                     │                   │                   │
          ┌──────────▼───────────────────▼───────────────────▼──────┐
          │                   Servicios Externos                      │
          │   API REST  │  Broker RabbitMQ  │  Servicio Web SOAP     │
          └────────────────────────────────────────────────────────┘
```

---

## Mapa de Componentes

### Capa Core

| Clase | Namespace | Archivo | Rol |
|-------|-----------|---------|-----|
| `gateway` | `local_integrationhub` | `classes/gateway.php` | **Orquestador principal.** Singleton. API pública para todos los plugins. Coordina resolución de servicios, circuit breaking, selección de transporte, reintentos y logging. |
| `gateway_response` | `local_integrationhub` | `classes/gateway_response.php` | **Value object inmutable.** Encapsula el resultado de cada petición independientemente del transporte. |

### Capa de Servicios

| Clase | Namespace | Archivo | Rol |
|-------|-----------|---------|-----|
| `registry` | `local_integrationhub\service` | `classes/service/registry.php` | **Acceso a datos.** Operaciones CRUD sobre la tabla `local_integrationhub_svc`. También inicializa el registro del circuit breaker al crear un servicio. |
| `circuit_breaker` | `local_integrationhub\service` | `classes/service/circuit_breaker.php` | **Tolerancia a fallos.** Rastrea contadores de fallos y gestiona transiciones de estado CLOSED/OPEN/HALFOPEN. |
| `retry_policy` | `local_integrationhub\service` | `classes/service/retry_policy.php` | **Resiliencia.** Ejecuta un callable con reintentos configurables y backoff exponencial. |

### Capa de Transporte

| Clase | Namespace | Archivo | Rol |
|-------|-----------|---------|-----|
| `contract` | `local_integrationhub\transport` | `classes/transport/contract.php` | **Interfaz.** Define el contrato `execute()` que todos los drivers deben implementar. |
| `http` | `local_integrationhub\transport` | `classes/transport/http.php` | **Driver REST.** Usa cURL nativo de PHP. Soporta GET, POST, PUT, PATCH, DELETE. |
| `amqp` | `local_integrationhub\transport` | `classes/transport/amqp.php` | **Driver RabbitMQ.** Usa `php-amqplib`. Publica mensajes JSON en exchanges o colas. |
| `amqp_helper` | `local_integrationhub\transport` | `classes/transport/amqp_helper.php` | **Utilidades AMQP.** Centraliza la creación de conexiones (plain y SSL) y la declaración de colas. |
| `soap` | `local_integrationhub\transport` | `classes/transport/soap.php` | **Driver SOAP.** Usa el `SoapClient` nativo de PHP. |
| `transport_utils` | `local_integrationhub\transport` | `classes/transport/transport_utils.php` | **Trait.** Helpers compartidos para construir arrays `success_result` y `error_result`. |

### Capa de Eventos

| Clase | Namespace | Archivo | Rol |
|-------|-----------|---------|-----|
| `observer` | `local_integrationhub\event` | `classes/event/observer.php` | **Listener universal.** Registrado contra `\core\event\base` para capturar todos los eventos de Moodle. Realiza búsqueda de reglas, deduplicación y encolado de tareas adhoc. |
| `webhook_received` | `local_integrationhub\event` | `classes/event/webhook_received.php` | **Evento personalizado.** Se dispara cuando se recibe un webhook entrante. |

### Capa de Tareas

| Clase | Namespace | Archivo | Rol |
|-------|-----------|---------|-----|
| `dispatch_event_task` | `local_integrationhub\task` | `classes/task/dispatch_event_task.php` | **Tarea adhoc.** Procesa un evento encolado: carga la regla, interpola el template, llama al Gateway, maneja DLQ en fallo permanente. |
| `consume_responses_task` | `local_integrationhub\task` | `classes/task/consume_responses_task.php` | **Tarea programada.** Se ejecuta cada minuto. Consume mensajes entrantes de colas de respuesta AMQP configuradas. |

---

## Caminos de Ejecución

### Camino 1: Llamada Directa desde Plugin (Síncrono)

```
Código PHP del plugin
  └─► gateway::instance()->request(nombre, endpoint, payload, método)
        ├─► registry::get_service(nombre)          [lectura DB]
        ├─► circuit_breaker::is_available()        [lectura DB]
        ├─► get_transport_driver(tipo)             [factory]
        ├─► retry_policy::execute(fn)              [bucle]
        │     └─► transport::execute(...)          [red]
        ├─► circuit_breaker::record_*()            [escritura DB]
        ├─► log_request(...)                       [escritura DB]
        └─► return gateway_response
```

### Camino 2: Event Bridge (Asíncrono)

```
Acción del usuario en Moodle
  └─► evento disparado
        └─► observer::handle_event(evento)
              ├─► DB: buscar reglas coincidentes
              ├─► caché: verificar deduplicación
              └─► queue_adhoc_task(dispatch_event_task)  [no bloqueante]

[Moodle cron, ~1 min después]
  └─► dispatch_event_task::execute()
        ├─► cargar regla + servicio desde DB
        ├─► interpolar template de payload
        ├─► gateway::instance()->request(...)
        └─► en fallo permanente: move_to_dlq()
```

---

## Decisiones de Diseño Clave

### Gateway como Singleton

`gateway` es un singleton (patrón `instance()`). Evita el overhead de reinstanciar la clase en cada llamada y facilita el mocking en tests.

### Transporte como Patrón Strategy

La capa de transporte usa el patrón Strategy mediante la interfaz `contract`. El Gateway selecciona el driver correcto en tiempo de ejecución basándose en el campo `type` del servicio. Agregar un nuevo transporte (ej. gRPC) solo requiere implementar `contract` y registrarlo en `get_transport_driver()`.

### Deduplicación via Caché

La deduplicación de eventos usa la caché de aplicación de Moodle (`local_integrationhub/event_dedupe`). La clave de caché es un hash SHA1 de `eventname + objectid + userid + crud`. Esto previene que se encolen tareas adhoc duplicadas cuando el mismo evento lógico se dispara múltiples veces.

### DLQ Después de 5 Intentos

El sistema de tareas adhoc de Moodle tiene su propio mecanismo de reintentos. `dispatch_event_task` rastrea su propio contador de intentos en `custom_data`. Después de 5 intentos totales, deja de relanzar la excepción y en su lugar escribe el payload fallido en `local_integrationhub_dlq`, previniendo bucles infinitos de reintentos.

---

## Relaciones de Base de Datos

```
local_integrationhub_svc (1)
    ├── (1) local_integrationhub_cb      [estado del circuit]
    ├── (N) local_integrationhub_log     [log de peticiones]
    ├── (N) local_integrationhub_rules   [reglas de eventos]
    └── (N) local_integrationhub_dlq     [dead letters]
```

Todas las claves foráneas se eliminan en cascada — borrar un servicio limpia todos los registros asociados.
