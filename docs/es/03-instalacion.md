# Instalación y Configuración

Este documento cubre todo lo necesario para instalar MIH en un entorno Moodle y configurarlo para su primer uso.

---

## Requisitos

| Requisito | Versión Mínima | Notas |
|-----------|----------------|-------|
| Moodle | 4.1 | Probado hasta 4.5 |
| PHP | 8.0 | 8.1+ recomendado |
| MySQL / MariaDB | 5.7 / 10.4 | PostgreSQL también soportado |
| `php-amqplib` | 3.x | **Solo requerido para soporte AMQP/RabbitMQ** |
| RabbitMQ | 3.8+ | Solo si se usa el transporte AMQP |

### Extensiones PHP Requeridas

- `curl` — para el transporte HTTP
- `soap` — para el transporte SOAP (normalmente incluido con PHP)
- `json` — siempre disponible en PHP 8+
- `openssl` — para conexiones AMQPS (SSL)

---

## Pasos de Instalación

### Paso 1: Copiar el Plugin

```bash
cp -r integrationhub /ruta/a/moodle/local/
```

O clonar directamente en el directorio de Moodle:

```bash
cd /ruta/a/moodle/local
git clone https://github.com/tu-org/integrationhub.git integrationhub
```

### Paso 2: Instalar Dependencias de Composer (solo AMQP)

Si planeas usar el transporte AMQP (RabbitMQ), instala la librería PHP AMQP:

```bash
cd /ruta/a/moodle/local/integrationhub
composer require php-amqplib/php-amqplib
```

> **Nota:** Si no vas a usar AMQP, omite este paso. Los transportes HTTP y SOAP no tienen dependencias de Composer.

### Paso 3: Ejecutar la Actualización de Moodle

Via CLI (recomendado para producción):

```bash
php /ruta/a/moodle/admin/cli/upgrade.php
```

O via la interfaz web:

1. Inicia sesión como Administrador del Sitio
2. Ve a **Administración del Sitio > Notificaciones**
3. Sigue las instrucciones de actualización

### Paso 4: Verificar las Tablas de Base de Datos

Después de la actualización, confirma que se crearon las siguientes tablas:

```sql
SHOW TABLES LIKE 'local_integrationhub%';
```

Resultado esperado:
```
local_integrationhub_cb
local_integrationhub_dlq
local_integrationhub_log
local_integrationhub_rules
local_integrationhub_svc
```

### Paso 5: Verificar las Tareas Programadas

Confirma que la tarea programada está registrada:

```bash
php admin/cli/scheduled_task.php --list | grep integrationhub
```

Resultado esperado:
```
\local_integrationhub\task\consume_responses_task   Cada minuto
```

---

## Configuración del Plugin

Ve a **Administración del Sitio > Servidor > Integration Hub**.

| Configuración | Clave | Por Defecto | Descripción |
|---------------|-------|-------------|-------------|
| Máximo de entradas de log | `max_log_entries` | `500` | Máximo de filas en `local_integrationhub_log` antes de la auto-purga. Pon `0` para desactivar la purga. |

---

## Configuración de Permisos

Por defecto, la capacidad `manage` se asigna a los roles `manager` y `admin`. La capacidad `view` se asigna a `manager`, `admin` y `editingteacher`.

Para personalizar:

1. Ve a **Administración del Sitio > Usuarios > Permisos > Definir Roles**
2. Edita el rol deseado
3. Busca `local/integrationhub`
4. Asigna `local/integrationhub:manage` y/o `local/integrationhub:view`

---

## Configuración del Cron de Moodle

El Event Bridge depende del sistema cron de Moodle para procesar tareas adhoc. Asegúrate de que el cron se ejecute al menos cada minuto:

```bash
# Entrada de crontab recomendada
* * * * * /usr/bin/php /ruta/a/moodle/admin/cli/cron.php > /dev/null 2>&1
```

---

## Solución de Problemas de Instalación

### Las tablas no se crearon

Si faltan las tablas después de la actualización, ejecuta:

```bash
php admin/cli/upgrade.php --non-interactive
```

Revisa el log de errores de Moodle para errores XMLDB.

### Clase AMQP no encontrada

Si ves `Class 'PhpAmqpLib\Connection\AMQPStreamConnection' not found`:

```bash
cd /ruta/a/moodle/local/integrationhub
composer install
```

Asegúrate de que `vendor/autoload.php` existe.

### El observer no se dispara

Purga las cachés de Moodle:

```bash
php admin/cli/purge_caches.php
```
