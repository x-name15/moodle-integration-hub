# Resiliencia: Circuit Breaker, Política de Reintentos y Dead Letter Queue

MIH está construido con la resiliencia como preocupación de primer nivel. Este documento explica los tres mecanismos que protegen a Moodle de fallos en servicios externos.

---

## Visión General

| Mecanismo | Propósito | Alcance |
|-----------|-----------|---------|
| **Circuit Breaker** | Previene llamar a servicios que se sabe que están caídos | Por servicio |
| **Política de Reintentos** | Reintenta automáticamente fallos transitorios | Por petición |
| **Dead Letter Queue** | Almacena eventos fallidos permanentemente para revisión | Por regla de evento |

---

## Circuit Breaker

### Estados

| Estado | Comportamiento |
|--------|---------------|
| **CLOSED** | Normal. Todas las peticiones pasan. El contador de fallos se incrementa en cada fallo. |
| **OPEN** | Disparado. Todas las peticiones fallan inmediatamente sin llamada de red. |
| **HALFOPEN** | Sonda de recuperación. Se permite una petición. Si tiene éxito → CLOSED. Si falla → OPEN. |

### Transiciones de Estado

| De | A | Condición |
|----|---|-----------|
| CLOSED | OPEN | `failure_count >= cb_failure_threshold` |
| OPEN | HALFOPEN | `time() - last_failure >= cb_cooldown` |
| HALFOPEN | CLOSED | La siguiente petición tiene éxito |
| HALFOPEN | OPEN | La siguiente petición falla |

### Configuración

Configuración por servicio (configurable en el dashboard):

| Configuración | Columna DB | Por Defecto | Descripción |
|---------------|------------|-------------|-------------|
| Umbral de Fallos | `cb_failure_threshold` | `5` | Fallos consecutivos antes de abrir |
| Cooldown | `cb_cooldown` | `30` | Segundos antes de intentar recuperación |

### Reset Manual

Desde el dashboard, haz clic en **Resetear Circuito** para forzar un servicio de vuelta a CLOSED. Úsalo cuando:
- Has confirmado que el servicio externo se ha recuperado
- Quieres probar un servicio sin esperar el cooldown
- Un falso positivo disparó el circuito (ej. un blip de red puntual)

---

## Política de Reintentos

### Algoritmo

MIH usa **backoff exponencial** — cada reintento espera el doble que el anterior, con un máximo de 60 segundos:

```
delay(intento) = min(backoff * 2^(intento-1), 60)
```

Con `max_retries = 3` y `retry_backoff = 1`:

| Intento | Delay Antes de Este Intento |
|---------|----------------------------|
| 1 (inicial) | 0s (inmediato) |
| 2 (reintento 1) | 1s |
| 3 (reintento 2) | 2s |
| 4 (reintento 3) | 4s |

### Configuración

| Configuración | Columna DB | Por Defecto | Descripción |
|---------------|------------|-------------|-------------|
| Máx. Reintentos | `max_retries` | `3` | Intentos adicionales tras el primer fallo |
| Backoff Inicial | `retry_backoff` | `1` | Segundos antes del primer reintento |

### Qué Dispara un Reintento

La política de reintentos reintenta ante **cualquier excepción** lanzada por el driver de transporte:
- Timeouts de red (timeout de cURL)
- Conexión rechazada
- Fallo de resolución DNS
- Errores de conexión AMQP

**No** reintenta automáticamente basándose en códigos de estado HTTP. Una respuesta `500 Internal Server Error` se devuelve como un `gateway_response` fallido pero no dispara un reintento (el transporte devuelve un resultado, no una excepción).

---

## Dead Letter Queue (DLQ)

### Propósito

La DLQ es una red de seguridad para el Event Bridge. Cuando un evento no puede entregarse después de todos los intentos de reintento, se almacena en la DLQ en lugar de descartarse silenciosamente.

### Cuándo van Eventos a la DLQ

1. `dispatch_event_task` falla (excepción lanzada)
2. Moodle reintenta la tarea (hasta el límite propio de Moodle)
3. MIH rastrea su propio contador de intentos en `custom_data`
4. Después de **5 intentos totales**, la tarea llama a `move_to_dlq()` y retorna sin relanzar
5. Moodle marca la tarea como completa (sin más reintentos)

### Revisando la DLQ

Navega a `/local/integrationhub/queue.php`:
- Ver todos los eventos fallidos con sus mensajes de error
- Ver el payload exacto que se intentó
- Identificar patrones (ej. todos los fallos para un servicio = servicio caído)

### Reenviando Eventos de la DLQ

Haz clic en **Reenviar** en cualquier entrada de la DLQ para re-encolarla como una nueva tarea adhoc.

Usa el reenvío cuando:
- El servicio externo se ha recuperado
- Corregiste un bug en el template de payload
- Un problema de red fue temporal

---

## Recomendaciones de Ajuste

### Producción de Alto Tráfico

```
cb_failure_threshold = 10    (más tolerancia a fallos ocasionales)
cb_cooldown          = 60    (ventana de recuperación más larga)
max_retries          = 2     (menos reintentos para no bloquear el cron)
retry_backoff        = 1     (reintentos rápidos)
timeout              = 3     (timeout corto para fallar rápido)
```

### Bajo Tráfico / Desarrollo

```
cb_failure_threshold = 3     (disparar rápido para detectar problemas)
cb_cooldown          = 10    (recuperar rápido para pruebas)
max_retries          = 3     (reintentos estándar)
retry_backoff        = 1
timeout              = 10    (más tolerante para servidores de dev lentos)
```

### Integraciones Críticas (No Deben Perder Eventos)

```
max_retries          = 5     (más reintentos antes de DLQ)
retry_backoff        = 2     (backoff más largo)
cb_failure_threshold = 20    (circuito muy tolerante)
cb_cooldown          = 120   (cooldown largo)
```

Y monitorea la DLQ regularmente para capturar cualquier evento que termine allí.
