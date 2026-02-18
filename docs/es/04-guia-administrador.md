# Guía de Administrador

Esta guía cubre la administración diaria de Moodle Integration Hub: registrar servicios, crear reglas de eventos, monitorear el dashboard y gestionar la Dead Letter Queue.

---

## Acceder al Plugin

Navega a `/local/integrationhub/index.php` o usa la pestaña **Servicios** en la navegación del plugin.

El plugin tiene cuatro secciones principales:

| Pestaña | URL | Descripción |
|---------|-----|-------------|
| **Servicios** | `/local/integrationhub/index.php` | Registrar y gestionar servicios externos |
| **Reglas** | `/local/integrationhub/rules.php` | Crear reglas del Event Bridge |
| **Cola** | `/local/integrationhub/queue.php` | Monitorear la Dead Letter Queue |
| **Eventos** | `/local/integrationhub/events.php` | Ver el log de eventos enviados |

---

## Gestión de Servicios

### Agregar un Servicio REST/HTTP

1. Ve a la pestaña **Servicios**
2. Haz clic en **Agregar Servicio**
3. Completa el formulario:

#### Configuración Básica

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Slug único. Sin espacios. Usado en llamadas PHP: `gateway->request('este-nombre', ...)` | `api-notificaciones` |
| **Tipo** | Protocolo de transporte | `REST` |
| **URL Base** | URL raíz. Las rutas de endpoint se añaden a esta | `https://api.ejemplo.com/v1` |
| **Activo** | Activar/desactivar sin borrar | marcado |

#### Autenticación

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Tipo de Auth** | `Bearer` envía `Authorization: Bearer {token}`. `API Key` envía `X-API-Key: {token}` | `Bearer` |
| **Token / API Key** | El valor de la credencial | `eyJhbGci...` |

#### Configuración de Resiliencia

| Campo | Descripción | Recomendado |
|-------|-------------|-------------|
| **Timeout (s)** | Segundos antes de cancelar la petición | `5` |
| **Máx. Reintentos** | Intentos adicionales tras el primer fallo | `3` |
| **Backoff Inicial (s)** | Segundos antes del primer reintento. Se duplica en cada intento | `1` |
| **Umbral de Fallos CB** | Fallos consecutivos antes de abrir el circuito | `5` |
| **Cooldown CB (s)** | Segundos antes de intentar recuperación (HALFOPEN) | `30` |

---

### Agregar un Servicio AMQP (RabbitMQ)

Cuando **Tipo = AMQP**, el formulario muestra un **Constructor de Conexión** en lugar de un campo de URL simple.

#### Campos del Constructor de Conexión

| Campo | Descripción | Por Defecto |
|-------|-------------|-------------|
| **Host** | Hostname o IP del broker RabbitMQ | `localhost` |
| **Puerto** | `5672` para AMQP plano, `5671` para AMQPS (SSL) | `5672` |
| **Usuario** | Nombre de usuario de RabbitMQ | `guest` |
| **Contraseña** | Contraseña de RabbitMQ | `guest` |
| **Virtual Host** | VHost de RabbitMQ | `/` |
| **Exchange** | Nombre del exchange destino. Vacío para el exchange por defecto | *(vacío)* |
| **Routing Key** | Routing key por defecto para mensajes publicados | `events.moodle` |
| **Cola a Declarar** | Cola a auto-declarar al conectar. Útil para desarrollo | `moodle_events` |
| **Dead Letter Queue** | Nombre de cola para mensajes que no se pueden entregar | `moodle_dlq` |

La URL de conexión se construye automáticamente:

```
amqp://usuario:contraseña@host:5672/vhost?exchange=X&routing_key=Y&queue_declare=Z&dlq=DLQ
```

---

### Agregar un Servicio SOAP

| Campo | Valor |
|-------|-------|
| **URL Base** | La URL WSDL: `https://servicio.ejemplo.com/api?wsdl` |
| **Tipo** | `SOAP` |

Al llamar via Gateway o Event Bridge, el campo `endpoint` es el **nombre del método SOAP**:

```php
$gateway->request('soap-legado', 'CrearUsuario', ['nombre' => 'Juan', 'email' => 'j@ejemplo.com']);
```

---

## Gestión de Reglas (Event Bridge)

### Agregar una Regla

1. Ve a la pestaña **Reglas**
2. Haz clic en **Agregar Regla**

#### Campos de la Regla

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Evento** | Nombre completo de clase PHP del evento de Moodle | `\core\event\user_created` |
| **Servicio** | Servicio destino (solo aparecen servicios activos) | `api-notificaciones` |
| **Método HTTP** | Para servicios REST: `POST`, `GET`, `PUT`, `PATCH`, `DELETE` | `POST` |
| **Endpoint** | Ruta añadida a la URL base del servicio (opcional). Para AMQP: routing key override | `/webhooks/usuarios` |
| **Template de Payload** | Template JSON con placeholders `{{variable}}` | Ver abajo |
| **Activo** | Activar/desactivar sin borrar | marcado |

#### Sintaxis del Template de Payload

```json
{
  "evento": "{{eventname}}",
  "id_usuario": {{userid}},
  "id_objeto": {{objectid}},
  "id_curso": {{courseid}},
  "timestamp": {{timecreated}},
  "fuente": "moodle"
}
```

> **Importante:** Los valores numéricos (`{{userid}}`, `{{objectid}}`, etc.) NO deben ir entre comillas en el template — se reemplazarán con enteros crudos. Los valores de texto (`{{eventname}}`, `{{ip}}`) deben ir entre comillas.

#### Vista Previa del Payload

Haz clic en **Vista Previa del Payload** para ver el resultado interpolado con datos de prueba antes de guardar la regla.

---

### Eventos Comunes de Moodle

| Evento | Descripción |
|--------|-------------|
| `\core\event\user_created` | Se creó una nueva cuenta de usuario |
| `\core\event\user_updated` | Se actualizó el perfil de un usuario |
| `\core\event\user_deleted` | Se eliminó un usuario |
| `\core\event\course_created` | Se creó un nuevo curso |
| `\core\event\course_completed` | Un usuario completó un curso |
| `\core\event\user_enrolment_created` | Un usuario se matriculó en un curso |
| `\core\event\user_enrolment_deleted` | Un usuario fue desmatriculado |
| `\core\event\grade_item_updated` | Se actualizó una calificación |
| `\core\event\user_loggedin` | Un usuario inició sesión |
| `\core\event\user_loggedout` | Un usuario cerró sesión |

---

## Monitoreo del Dashboard

El dashboard principal (`/local/integrationhub/index.php`) proporciona:

### Gráficas

| Gráfica | Descripción |
|---------|-------------|
| **Distribución de Estado** | Gráfica de pastel mostrando la proporción de peticiones exitosas vs. fallidas |
| **Tendencia de Latencia** | Gráfica de línea mostrando los tiempos de respuesta de las últimas 200 peticiones |

### Tabla de Servicios

| Columna | Descripción |
|---------|-------------|
| **Nombre** | Slug del servicio |
| **Tipo** | REST / AMQP / SOAP |
| **Circuito** | Estado actual: CLOSED (verde), OPEN (rojo), HALFOPEN (amarillo) |
| **Latencia Prom.** | Tiempo de respuesta promedio en las últimas 24 horas |
| **Errores (24h)** | Número de peticiones fallidas en las últimas 24 horas |
| **Último Uso** | Timestamp de la petición más reciente |
| **Acciones** | Editar, Eliminar, Resetear Circuito |

### Resetear un Circuito

Si un servicio se ha recuperado pero su circuito sigue en OPEN:

1. Haz clic en **Resetear Circuito** en la fila del servicio
2. El circuito transiciona a CLOSED y el contador de fallos se resetea

---

## Dead Letter Queue (DLQ)

Cuando un evento falla en despacharse después de 5 intentos, se mueve a la DLQ.

Navega a `/local/integrationhub/queue.php` para:

- Ver todos los eventos fallidos con sus mensajes de error y payloads
- **Reenviar** eventos individuales (los re-encola como una nueva tarea adhoc)
- **Eliminar** eventos que ya no son necesarios

### Cuándo Terminan Eventos en la DLQ

- El servicio destino está permanentemente caído y el circuito nunca se recupera
- El template de payload produce JSON inválido
- El servicio fue eliminado después de crear la regla
- Un error de red persiste más allá de 5 intentos de reintento
