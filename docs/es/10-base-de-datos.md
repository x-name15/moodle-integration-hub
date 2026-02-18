# Esquema de Base de Datos

Este documento describe cada tabla en el esquema de base de datos de MIH, incluyendo definiciones de columnas, restricciones, índices y notas de uso.

---

## Visión General

MIH usa cinco tablas, todas con el prefijo `local_integrationhub_`:

| Tabla | Propósito | Filas (típico) |
|-------|-----------|----------------|
| `svc` | Servicios externos registrados | 1–50 |
| `cb` | Estado del circuit breaker (una fila por servicio) | Igual que `svc` |
| `log` | Log de peticiones/respuestas (auto-purgado) | Hasta `max_log_entries` |
| `rules` | Reglas del Event Bridge | 1–500 |
| `dlq` | Dead Letter Queue para eventos fallidos | 0–∞ (limpieza manual) |

---

## `local_integrationhub_svc` — Servicios

| Columna | Tipo | Nulable | Por Defecto | Descripción |
|---------|------|---------|-------------|-------------|
| `id` | BIGINT | No | auto | Clave primaria |
| `name` | VARCHAR(255) | No | — | Slug único. Usado como clave de búsqueda en `gateway->request()`. Sin espacios. |
| `type` | VARCHAR(10) | No | `rest` | Tipo de transporte: `rest`, `amqp`, o `soap` |
| `base_url` | VARCHAR(1333) | No | — | URL base para REST/SOAP, o cadena de conexión AMQP completa |
| `auth_type` | VARCHAR(20) | Sí | `null` | Método de autenticación: `bearer` o `apikey` |
| `auth_token` | LONGTEXT | Sí | `null` | Valor del token o API key |
| `timeout` | BIGINT | No | `5` | Timeout de petición en segundos |
| `max_retries` | BIGINT | No | `3` | Intentos máximos de reintento tras el primer fallo |
| `retry_backoff` | BIGINT | No | `1` | Backoff inicial en segundos (se duplica en cada reintento) |
| `cb_failure_threshold` | BIGINT | No | `5` | Fallos consecutivos antes de abrir el circuito |
| `cb_cooldown` | BIGINT | No | `30` | Segundos antes de intentar recuperación (HALFOPEN) |
| `response_queue` | VARCHAR(255) | Sí | `null` | Nombre de cola AMQP para consumo de respuestas entrantes |
| `enabled` | TINYINT(1) | No | `1` | `1` = activo, `0` = desactivado |
| `timecreated` | BIGINT | No | — | Timestamp Unix de creación |
| `timemodified` | BIGINT | No | — | Timestamp Unix de última modificación |

---

## `local_integrationhub_cb` — Circuit Breaker

Una fila por servicio. Rastrea el estado del circuit breaker.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `serviceid` | BIGINT | FK → `local_integrationhub_svc.id` |
| `state` | VARCHAR(10) | Estado actual: `closed`, `open`, o `halfopen` |
| `failure_count` | BIGINT | Contador de fallos consecutivos. Se resetea a 0 en éxito. |
| `last_failure` | BIGINT | Timestamp Unix del fallo más reciente |
| `timemodified` | BIGINT | Timestamp Unix del último cambio de estado |

---

## `local_integrationhub_log` — Log de Peticiones

Registra cada petición saliente (y entrante AMQP).

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `serviceid` | BIGINT | FK → `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(1333) | Ruta de endpoint llamada |
| `http_method` | VARCHAR(10) | Método HTTP usado |
| `http_status` | BIGINT | Código de respuesta HTTP. `null` para AMQP. |
| `latency_ms` | BIGINT | Tiempo de respuesta en milisegundos |
| `attempt_count` | BIGINT | Total de intentos realizados (incluyendo reintentos) |
| `success` | TINYINT(1) | `1` = éxito, `0` = fallo |
| `error_message` | LONGTEXT | Descripción del error si falló |
| `direction` | VARCHAR(10) | `outbound` (MIH → servicio) o `inbound` (servicio → MIH) |
| `timecreated` | BIGINT | Timestamp Unix de la petición |

### Auto-Purga

Después de cada INSERT, el Gateway verifica el total de filas. Si supera `max_log_entries` (por defecto: 500), se eliminan las filas más antiguas.

---

## `local_integrationhub_rules` — Reglas del Event Bridge

Cada fila mapea un evento de Moodle a una llamada de servicio.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `eventname` | VARCHAR(255) | Nombre completo de clase PHP del evento de Moodle |
| `serviceid` | BIGINT | FK → `local_integrationhub_svc.id` |
| `endpoint` | VARCHAR(255) | Override de endpoint. Para AMQP: routing key. Para SOAP: nombre del método. |
| `http_method` | VARCHAR(10) | Método HTTP para servicios REST |
| `payload_template` | LONGTEXT | Template JSON con placeholders `{{variable}}` |
| `enabled` | TINYINT(1) | `1` = activo, `0` = desactivado |
| `timecreated` | BIGINT | Timestamp Unix de creación |
| `timemodified` | BIGINT | Timestamp Unix de última modificación |

---

## `local_integrationhub_dlq` — Dead Letter Queue

Almacena eventos que fallaron en entregarse después de todos los intentos de reintento.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `eventname` | VARCHAR(255) | Nombre de clase del evento que falló |
| `serviceid` | BIGINT | ID del servicio destino |
| `payload` | LONGTEXT | Payload JSON-encoded que se intentó |
| `error_message` | LONGTEXT | Último mensaje de error del intento fallido |
| `timecreated` | BIGINT | Timestamp Unix cuando el evento fue movido a DLQ |

---

## Consultas Útiles

### Servicios con circuitos abiertos

```sql
SELECT s.name, cb.state, cb.failure_count, cb.last_failure
FROM local_integrationhub_svc s
JOIN local_integrationhub_cb cb ON cb.serviceid = s.id
WHERE cb.state != 'closed'
ORDER BY cb.last_failure DESC;
```

### Tasa de errores por servicio (últimas 24h)

```sql
SELECT
    s.name,
    COUNT(*) AS total,
    SUM(CASE WHEN l.success = 0 THEN 1 ELSE 0 END) AS errores,
    AVG(l.latency_ms) AS latencia_prom_ms
FROM local_integrationhub_log l
JOIN local_integrationhub_svc s ON s.id = l.serviceid
WHERE l.timecreated > UNIX_TIMESTAMP() - 86400
GROUP BY s.id, s.name
ORDER BY errores DESC;
```

### Entradas DLQ por servicio

```sql
SELECT s.name, COUNT(*) AS dlq_count, MAX(d.timecreated) AS ultimo_fallo
FROM local_integrationhub_dlq d
JOIN local_integrationhub_svc s ON s.id = d.serviceid
GROUP BY s.id, s.name
ORDER BY dlq_count DESC;
```
