# Drivers de Transporte

MIH soporta tres protocolos de transporte: HTTP/REST, AMQP (RabbitMQ) y SOAP. Cada uno está implementado como una clase driver que implementa la interfaz `transport\contract`.

---

## HTTP / REST

**Clase:** `local_integrationhub\transport\http`
**Usa:** cURL nativo de PHP

### Cómo Funciona

1. Construye la URL completa: `{base_url}/{endpoint}`
2. Establece cabeceras: `Content-Type: application/json`, `Accept: application/json`
3. Aplica cabecera de autenticación según `auth_type`
4. Serializa `$payload` a JSON para el cuerpo de la petición (o query string para GET)
5. Ejecuta la petición cURL con el timeout configurado
6. Determina el éxito basándose en el código de estado HTTP (2xx = éxito)

### Autenticación

| `auth_type` | Cabecera Enviada |
|-------------|-----------------|
| `bearer` | `Authorization: Bearer {auth_token}` |
| `apikey` | `X-API-Key: {auth_token}` |
| *(vacío)* | Sin cabecera de autenticación |

### Métodos HTTP

| Método | Ubicación del Payload |
|--------|----------------------|
| `GET` | Query string (valores JSON-encoded) |
| `POST` | Cuerpo de la petición (JSON) |
| `PUT` | Cuerpo de la petición (JSON) |
| `PATCH` | Cuerpo de la petición (JSON) |
| `DELETE` | Cuerpo de la petición (JSON) |

### Criterio de Éxito

Una respuesta se considera exitosa si el código de estado HTTP está en el rango `2xx` (200–299).

---

## AMQP / RabbitMQ

**Clase:** `local_integrationhub\transport\amqp`
**Helper:** `local_integrationhub\transport\amqp_helper`
**Requiere:** `php-amqplib/php-amqplib` (Composer)

### Formato de URL de Conexión

```
amqp://usuario:contraseña@host:puerto/vhost?exchange=X&routing_key=Y&queue_declare=Z&dlq=DLQ
```

| Componente URL | Descripción | Por Defecto |
|----------------|-------------|-------------|
| `scheme` | `amqp` (plano) o `amqps` (SSL/TLS) | `amqp` |
| `user` | Usuario de RabbitMQ | `guest` |
| `password` | Contraseña de RabbitMQ | `guest` |
| `host` | Hostname o IP del broker | `localhost` |
| `port` | Puerto del broker | `5672` (amqp), `5671` (amqps) |
| `vhost` | Virtual host (URL-encoded) | `/` |
| `exchange` | Nombre del exchange (parámetro query) | *(exchange por defecto)* |
| `routing_key` | Routing key por defecto (parámetro query) | *(vacío)* |
| `queue_declare` | Cola a auto-declarar (parámetro query) | *(ninguna)* |
| `dlq` | Nombre de cola dead letter (parámetro query) | *(ninguna)* |

### Resolución del Routing Key

El routing key se determina en este orden:

1. El parámetro `endpoint` pasado a `gateway->request()` (mayor prioridad)
2. El parámetro query `routing_key` en la URL de conexión
3. El nombre de cola `queue_declare` (fallback para patrón directo a cola)

### Propiedades del Mensaje

Los mensajes publicados tienen:
- `delivery_mode = DELIVERY_MODE_PERSISTENT` — los mensajes sobreviven reinicios del broker
- `content_type = application/json`

### SSL/TLS (AMQPS)

Usa `amqps://` en la URL de conexión para conexiones SSL (puerto 5671):

```
amqps://usuario:contraseña@rabbitmq.ejemplo.com:5671/produccion
```

---

## SOAP

**Clase:** `local_integrationhub\transport\soap`
**Usa:** `SoapClient` nativo de PHP

### Configuración del Servicio

| Campo | Valor |
|-------|-------|
| **URL Base** | La URL WSDL: `https://servicio.ejemplo.com/api?wsdl` |
| **Tipo** | `SOAP` |

### Llamar a un Método SOAP

```php
// endpoint = nombre del método SOAP
$response = $gateway->request(
    'servicio-soap-legado',
    'ObtenerUsuarioPorId',    // Nombre del método SOAP
    ['UserId' => 42],          // Parámetros del método
);
```

### Opciones de SoapClient

```php
$options = [
    'connection_timeout' => $service->timeout,
    'exceptions'         => true,   // Lanzar SoapFault en errores
    'trace'              => true,   // Habilitar trazado de petición/respuesta
    'cache_wsdl'         => WSDL_CACHE_DISK, // Cachear WSDL en disco
];
```

---

## Agregar un Transporte Personalizado

Para agregar un nuevo transporte (ej. gRPC):

1. Crea `classes/transport/grpc.php` implementando `contract`
2. Regístralo en `gateway::get_transport_driver()`:

```php
return match($type) {
    'amqp' => new transport\amqp(),
    'soap' => new transport\soap(),
    'grpc' => new transport\grpc(),  // Agregar aquí
    default => new transport\http(),
};
```

3. Agrega la opción de tipo al formulario de servicio en `index.php`.
