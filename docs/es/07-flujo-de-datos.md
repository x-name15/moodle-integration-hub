# Flujo de Datos

Este documento proporciona diagramas detallados de extremo a extremo para cada camino de ejecución en MIH.

---

## Camino 1: Llamada Directa al Gateway (Síncrono)

```
Código PHP del Plugin
    │
    ▼
gateway::instance()
    │
    ▼
gateway->request('nombre-servicio', '/endpoint', $payload, 'POST')
    │
    ├─── [1] Resolución de Servicio
    │         service\registry::get_service('nombre-servicio')
    │         → Si no existe: throw moodle_exception('service_not_found')
    │         → Si desactivado: throw moodle_exception('service_disabled')
    │
    ├─── [2] Verificación del Circuit Breaker
    │         → state = 'open' Y cooldown no expirado
    │             → throw moodle_exception('circuit_open')
    │         → state = 'open' Y cooldown expirado
    │             → UPDATE cb SET state = 'halfopen'
    │             → continuar (petición de prueba)
    │         → state = 'closed' O 'halfopen'
    │             → continuar
    │
    ├─── [3] Selección de Transporte
    │         → 'rest'  → new transport\http()
    │         → 'amqp'  → new transport\amqp()
    │         → 'soap'  → new transport\soap()
    │
    ├─── [4] Bucle de Reintentos
    │         retry_policy::from_service($service)->execute(fn)
    │         ├─ Intento 1: transport->execute(...)
    │         ├─ Si excepción: sleep(backoff * 2^0) → Intento 2
    │         ├─ Si excepción: sleep(backoff * 2^1) → Intento 3
    │         └─ Si excepción: sleep(backoff * 2^2) → Intento 4 (si max_retries=3)
    │
    ├─── [5] Actualización del Circuit Breaker
    │         → En éxito: cb->record_success()
    │         → En fallo: cb->record_failure()
    │
    ├─── [6] Logging de Petición
    │         INSERT INTO local_integrationhub_log (...)
    │         → Auto-purga si count > max_log_entries
    │
    └─── [7] Retorno
              return new gateway_response(...)
```

---

## Camino 2: Event Bridge (Asíncrono)

### Fase A: Captura del Evento (Síncrono, en la petición del usuario)

```
Acción del usuario en Moodle
    │
    ▼
Moodle dispara evento: \core\event\user_created
    │
    ▼
event\observer::handle_event($event)
    │
    ├─── [1] Búsqueda de Reglas
    │         SELECT * FROM local_integrationhub_rules
    │         WHERE eventname = '\core\event\user_created' AND enabled = 1
    │         → Si vacío: return (nada que hacer)
    │
    ├─── [2] Deduplicación
    │         $sig = sha1(eventname + objectid + userid + crud)
    │         → Si $cache->get($sig): return (duplicado, omitir)
    │         → $cache->set($sig, 1)  [TTL: 60 segundos]
    │
    └─── [3] Encolado de Tareas
              Por cada regla coincidente:
                  $task = new dispatch_event_task()
                  $task->set_custom_data([...])
                  core\task\manager::queue_adhoc_task($task)

[La petición del usuario completa — sin esperar la integración]
```

### Fase B: Despacho del Evento (Asíncrono, en el cron de Moodle)

```
Cron de Moodle (~1 minuto después)
    │
    ▼
dispatch_event_task::execute()
    │
    ├─── [1] Cargar Regla y Servicio desde DB
    │
    ├─── [2] Preparar Payload
    │         Si template vacío: $payload = $eventdata
    │         Si template existe: interpolar {{variables}} → decodificar JSON
    │
    ├─── [3] Llamada al Gateway
    │         gateway::instance()->request(...)
    │         → Flujo completo del Camino 1
    │
    ├─── [4a] En Éxito
    │         mtrace("Éxito: HTTP 200")
    │         Tarea completa
    │
    └─── [4b] En Fallo
              $data->attempts++
              ├─ Si intentos < 5:
              │   throw $e → Moodle reintenta la tarea
              └─ Si intentos >= 5:
                  move_to_dlq($regla, $payload, $error)
                  return (sin más reintentos)
```

---

## Camino 3: Máquina de Estados del Circuit Breaker

```
Estado inicial: CLOSED (failure_count = 0)

CLOSED
  ├─ Petición exitosa → permanecer CLOSED
  └─ Petición fallida
       failure_count++
       ├─ failure_count < umbral → permanecer CLOSED
       └─ failure_count >= umbral → transición a OPEN

OPEN
  ├─ Nueva petición + cooldown no expirado
  │   → rechazar inmediatamente (throw circuit_open)
  └─ Nueva petición + cooldown expirado
       → transición a HALFOPEN
       → permitir una petición de prueba

HALFOPEN
  ├─ Petición de prueba exitosa → transición a CLOSED, failure_count = 0
  └─ Petición de prueba fallida → transición a OPEN, last_failure = time()
```

---

## Resumen de Escrituras en Base de Datos

| Operación | Tablas Escritas |
|-----------|----------------|
| Servicio creado | `svc`, `cb` (estado inicial) |
| Petición realizada | `log`, `cb` (actualización de estado) |
| Regla de evento coincidente | `task_adhoc` (cola) |
| Tarea despachada exitosamente | `log`, `cb` |
| Tarea fallida (< 5 intentos) | `log`, `cb`, `task_adhoc` (custom_data actualizado) |
| Tarea fallida permanentemente | `log`, `cb`, `dlq` |
| DLQ reenviada | `task_adhoc` (nueva tarea) |
| Circuito reseteado | `cb` |
| Log purgado | `log` (filas antiguas eliminadas) |
