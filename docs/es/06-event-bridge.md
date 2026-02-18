# Event Bridge — Despacho Automático de Eventos

El Event Bridge es el sistema de integración sin código de MIH. Permite a los administradores mapear cualquier evento de Moodle a cualquier llamada de servicio externo — sin escribir PHP.

---

## Cómo Funciona

En términos generales:

1. Un usuario hace algo en Moodle (inicia sesión, completa un curso, envía una tarea)
2. Moodle dispara un evento (una clase PHP que extiende `\core\event\base`)
3. El observer universal de MIH captura el evento
4. El observer busca reglas activas coincidentes en la base de datos
5. Por cada regla coincidente, se encola una tarea adhoc
6. El cron de Moodle recoge la tarea y llama al Gateway
7. El Gateway envía el payload al servicio configurado

---

## El Observer Universal

El observer está registrado en `db/events.php` contra `\core\event\base` — la clase base de **todos** los eventos en Moodle. Esto significa que MIH captura:

- Todos los eventos del core de Moodle (usuario, curso, calificación, matrícula, etc.)
- Todos los eventos de plugins de terceros
- Cualquier evento personalizado que definas en tus propios plugins

---

## Templates de Payload

Los templates definen el cuerpo JSON enviado al servicio externo. Usan placeholders `{{variable}}` que se reemplazan con valores de los datos del evento en el momento del despacho.

### Template Básico

```json
{
  "evento": "{{eventname}}",
  "id_usuario": {{userid}},
  "timestamp": {{timecreated}}
}
```

### Variables Disponibles

| Variable | Tipo | Descripción | Ejemplo |
|----------|------|-------------|---------|
| `{{eventname}}` | string | Nombre completo de clase del evento | `\core\event\user_created` |
| `{{userid}}` | int | ID del usuario que disparó el evento | `5` |
| `{{objectid}}` | int | ID del objeto primario afectado | `42` |
| `{{courseid}}` | int | ID del curso (0 si no es específico de curso) | `10` |
| `{{contextid}}` | int | ID del contexto de Moodle | `1` |
| `{{contextlevel}}` | int | Nivel de contexto (10=sistema, 50=curso, etc.) | `50` |
| `{{timecreated}}` | int | Timestamp Unix del evento | `1708258939` |
| `{{ip}}` | string | Dirección IP del usuario | `192.168.1.100` |
| `{{crud}}` | string | Tipo de operación: `c`rear, `r`ead, `u`pdate, `d`elete | `c` |

### Reemplazo con Conciencia de Tipos

- **Enteros** (`{{userid}}`, `{{objectid}}`, etc.) se reemplazan como números crudos — no los envuelvas en comillas
- **Strings** (`{{eventname}}`, `{{ip}}`) se escapan para JSON y deben ir entre comillas
- **Booleanos** se reemplazan como `true` o `false`

---

## Deduplicación

El observer usa la caché de aplicación de Moodle para prevenir procesamiento duplicado.

**Clave de deduplicación:**
```php
$signature = sha1($eventname . $event->objectid . $event->userid . $event->crud);
```

Si el mismo evento lógico se dispara dos veces en 60 segundos (misma clase de evento, mismo objeto, mismo usuario, misma operación), solo se procesa la primera ocurrencia.

---

## Tarea de Despacho

La tarea adhoc `dispatch_event_task` maneja la entrega real:

### Flujo de Ejecución

```
dispatch_event_task::execute()
    │
    ├── Cargar regla desde DB (verificar que existe y está activa)
    ├── Cargar servicio desde DB (verificar que existe y está activo)
    │
    ├── Preparar payload:
    │   ├── Si template vacío: usar datos crudos del evento
    │   └── Si template existe:
    │       ├── Reemplazar {{variables}} con valores del evento
    │       ├── Decodificar como JSON
    │       └── Si JSON inválido: lanzar excepción (la tarea reintentará)
    │
    ├── gateway::instance()->request(servicio, endpoint, payload, método)
    │
    ├── En éxito: mtrace mensaje de éxito, tarea completa
    │
    └── En fallo:
        ├── Incrementar contador de intentos en custom_data
        ├── Si intentos < 5: relanzar excepción (Moodle reintenta la tarea)
        └── Si intentos >= 5: move_to_dlq(), return (sin más reintentos)
```

---

## Ejemplos Prácticos

### Notificar a Slack Cuando un Usuario se Matricula

**Servicio:** `slack-webhook` (REST, POST a Slack Incoming Webhook URL)

**Evento:** `\core\event\user_enrolment_created`

**Template:**
```json
{
  "text": "Nueva matrícula: Usuario {{userid}} en curso {{courseid}}",
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Nueva Matrícula*\nID Usuario: {{userid}}\nID Curso: {{courseid}}\nHora: {{timecreated}}"
      }
    }
  ]
}
```

---

### Publicar en RabbitMQ al Completar un Curso

**Servicio:** `rabbitmq-prod` (AMQP)

**Evento:** `\core\event\course_completed`

**Endpoint (Routing Key):** `lms.events.course.completed`

**Template:**
```json
{
  "tipo_evento": "course_completed",
  "id_usuario": {{userid}},
  "id_curso": {{courseid}},
  "completado_en": {{timecreated}},
  "fuente": "moodle"
}
```

---

### Sincronizar Usuario con CRM al Actualizar Perfil

**Servicio:** `crm-api` (REST, PUT)

**Evento:** `\core\event\user_updated`

**Endpoint:** `/contactos/{{userid}}`

**Template:**
```json
{
  "moodle_id": {{userid}},
  "actualizado_en": {{timecreated}},
  "fuente": "moodle_lms"
}
```

---

## Limitaciones

- Las variables de template están limitadas a los campos planos en `$event->get_data()`. Los datos anidados (ej. contenido del array `other`) no son directamente accesibles via sintaxis `{{variable}}`.
- El observer se dispara en cada evento de Moodle — en sistemas de alto tráfico, asegúrate de que tus reglas sean específicas para evitar consultas DB innecesarias.
- La deduplicación se basa en una ventana de 60 segundos. Los eventos que legítimamente se disparan múltiples veces para diferentes objetos dentro de 60 segundos se procesarán correctamente (la firma incluye `objectid`).
