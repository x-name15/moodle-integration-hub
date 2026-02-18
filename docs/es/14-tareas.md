# Tareas Programadas y Adhoc

MIH usa el sistema de tareas de Moodle para el procesamiento en segundo plano.

---

## Visión General

| Tarea | Tipo | Clase | Horario por Defecto |
|-------|------|-------|---------------------|
| Consumir Respuestas AMQP | Programada | `task\consume_responses_task` | Cada minuto |
| Despachar Evento | Adhoc | `task\dispatch_event_task` | Bajo demanda (encolada por el observer) |

---

## Tarea Programada: `consume_responses_task`

**Clase:** `\local_integrationhub\task\consume_responses_task`
**Horario:** Cada minuto (`* * * * *`)

### Propósito

Consume mensajes entrantes de colas de respuesta AMQP. Esto habilita un patrón petición-respuesta sobre RabbitMQ: MIH publica un mensaje, el servicio externo lo procesa y publica una respuesta en una cola de respuesta, y esta tarea recoge la respuesta.

### Qué Hace

1. Consulta `local_integrationhub_svc` para todos los servicios AMQP activos que tienen una `response_queue` configurada
2. Por cada servicio:
   - Se conecta a RabbitMQ usando `amqp_helper::create_connection()`
   - Consume todos los mensajes pendientes de la `response_queue`
   - Por cada mensaje: parsea el JSON, registra en el log (con `direction = 'inbound'`), confirma el mensaje (`basic_ack`)
   - Cierra la conexión

### Cambiar el Horario

Via la UI de Moodle:
1. Ve a **Administración del Sitio > Servidor > Tareas Programadas**
2. Encuentra **Consumir Respuestas AMQP**
3. Haz clic en el icono de edición y cambia el horario

Via CLI:
```bash
php admin/cli/scheduled_task.php \
    --execute='\local_integrationhub\task\consume_responses_task'
```

---

## Tarea Adhoc: `dispatch_event_task`

**Clase:** `\local_integrationhub\task\dispatch_event_task`
**Tipo:** Adhoc (encolada bajo demanda)

### Propósito

Procesa un evento encolado y lo despacha al servicio externo configurado. Se crea una tarea por cada regla coincidente por cada ocurrencia de evento.

### Cuándo Se Ejecuta

Tan pronto como el cron de Moodle se ejecute después de encolar la tarea (típicamente dentro de 1 minuto del evento).

### Estructura de Custom Data

```php
[
    'ruleid'    => int,    // ID de la regla coincidente
    'eventdata' => array,  // $event->get_data() del observer
    'attempts'  => int,    // Contador de intentos propio de MIH (empieza en 0)
]
```

### Comportamiento de Reintentos

| Intento | Qué Ocurre |
|---------|-----------|
| 1–4 | En fallo: incrementar `attempts`, relanzar excepción → Moodle reintenta |
| 5 | En fallo: llamar `move_to_dlq()`, retornar → tarea completa, sin más reintentos |

### Monitorear Tareas Adhoc

Verificar la cola de tareas adhoc:

```bash
php admin/cli/adhoc_task.php --list
```

Ejecutar todas las tareas adhoc pendientes inmediatamente:

```bash
php admin/cli/adhoc_task.php --execute
```

Ejecutar una clase de tarea específica:

```bash
php admin/cli/adhoc_task.php \
    --execute='\local_integrationhub\task\dispatch_event_task'
```

### Depurar Fallos de Tareas

Las tareas fallidas aparecen en el log de tareas de Moodle:

1. Ve a **Administración del Sitio > Servidor > Logs de Tareas**
2. Filtra por clase `\local_integrationhub\task\dispatch_event_task`

La tarea emite mensajes detallados `mtrace()` incluyendo:
- El nombre del evento y servicio siendo llamado
- El payload interpolado
- El estado HTTP y cuerpo de respuesta
- El mensaje de error en caso de fallo

---

## Consideraciones de Rendimiento

- En instancias de Moodle de alto tráfico con muchas reglas de eventos, la cola de tareas adhoc puede crecer
- Considera usar el ejecutor paralelo de tareas de Moodle para mejor rendimiento:
  ```bash
  php admin/cli/adhoc_task.php --execute --parallel=4
  ```
- La `consume_responses_task` mantiene una conexión RabbitMQ abierta durante su ejecución — asegúrate de que los límites de conexión de tu broker estén configurados apropiadamente
