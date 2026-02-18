# Descripción General

**Moodle Integration Hub (MIH)** es un plugin local de Moodle que proporciona una capa de integración centralizada y lista para producción entre Moodle y cualquier servicio externo: APIs REST, brokers de mensajería (RabbitMQ) o servicios web SOAP.

---

## El Problema

Los despliegues modernos de Moodle rara vez son sistemas aislados. Se conectan a sistemas externos: motores de gamificación, plataformas de analítica, servicios de notificaciones, sistemas ERP, integraciones con SIS, y más. Sin una solución centralizada, esto crea un patrón de código duplicado y frágil:

- Cada plugin que necesita llamar a un servicio externo implementa su propia lógica HTTP
- Los tokens de autenticación están dispersos en múltiples archivos `settings.php`
- La lógica de reintentos y manejo de timeouts es inconsistente o directamente inexistente
- No hay un lugar central para monitorear qué está pasando en todas las integraciones
- Cuando un servicio externo cae, no hay protección — las peticiones de Moodle se acumulan y fallan silenciosamente
- Agregar una nueva integración requiere escribir código PHP, incluso para mapeos simples de evento-a-webhook

Este es el problema de la **dispersión de integraciones**. MIH lo resuelve.

---

## La Solución

MIH proporciona dos sistemas complementarios:

### 1. Gateway de Servicios

Una clase PHP singleton (`gateway`) que cualquier plugin de Moodle puede llamar para hacer peticiones HTTP o AMQP a servicios externos registrados. El Gateway se encarga de:

- **Resolución de servicios** — busca la configuración del servicio en la base de datos por nombre
- **Autenticación** — aplica automáticamente tokens Bearer o API keys
- **Circuit breaking** — rechaza llamadas a servicios que se sabe que están caídos
- **Reintentos con backoff** — reintenta peticiones fallidas con delays exponenciales
- **Logging** — registra cada petición, respuesta y error en una tabla de log centralizada
- **Encapsulación de respuestas** — devuelve un objeto `gateway_response` consistente independientemente del transporte

### 2. Event Bridge

Un sistema de integración sin código para mapear eventos de Moodle a llamadas de servicios externos. Los administradores configuran reglas en el dashboard — sin necesidad de PHP. El Event Bridge se encarga de:

- **Escucha universal de eventos** — un único observer captura todos los eventos de Moodle (core y de terceros)
- **Matching de reglas** — verifica qué reglas aplican a cada evento
- **Deduplicación** — previene que el mismo evento se procese dos veces
- **Despacho asíncrono** — encola el trabajo como tareas adhoc de Moodle para que las acciones del usuario nunca se bloqueen
- **Interpolación de templates** — construye payloads JSON a partir de datos del evento usando sintaxis `{{variable}}`
- **Dead Letter Queue** — almacena eventos que fallaron permanentemente para revisión y reenvío manual

---

## Capacidades Principales

| Capacidad | Descripción |
|-----------|-------------|
| **Gateway de Servicios** | API PHP reutilizable para llamadas HTTP/AMQP desde cualquier plugin |
| **Event Bridge** | Reacciona automáticamente a cualquier evento de Moodle sin escribir PHP |
| **Circuit Breaker** | Previene fallos en cascada cuando los servicios externos caen |
| **Reintentos con Backoff Exponencial** | Reintentos automáticos con delays configurables |
| **Dead Letter Queue (DLQ)** | Eventos fallidos almacenados para revisión y reenvío |
| **Dashboard de Monitoreo** | Gráficas en tiempo real de tasas de éxito y tendencias de latencia |
| **Multi-transporte** | Soporte para REST/HTTP, AMQP (RabbitMQ) y SOAP |
| **UI Multiidioma** | Soporte completo en inglés y español |
| **Auto-purga de Logs** | La tabla de logs se poda automáticamente para evitar crecimiento descontrolado |

---

## Filosofía de Diseño

MIH fue construido con los siguientes principios:

- **Centralización sobre duplicación** — un solo lugar para configurar, monitorear y depurar todas las integraciones
- **Resiliencia por defecto** — los circuit breakers y reintentos siempre están activos, no son opt-in
- **No bloqueante** — el Event Bridge usa tareas asíncronas para que las acciones del usuario nunca se retrasen por fallos de integración
- **Integraciones sin código** — los patrones comunes de evento-a-webhook no requieren PHP
- **Nativo de Moodle** — usa la capa DB, sistema de tareas, API de caché y renderizador de Moodle; sin frameworks externos

---

## Lo que MIH NO es

- MIH **no** es un reemplazo de los web services de Moodle (API REST/SOAP para que sistemas externos llamen a Moodle)
- MIH **no** es un ESB (Enterprise Service Bus) completo — está enfocado en integraciones salientes desde Moodle
- MIH **no** consume mensajes por defecto — el transporte AMQP publica mensajes; el consumo de respuestas lo maneja una tarea programada separada

---

## Próximos Pasos

- [Arquitectura](02-arquitectura.md) — entiende cómo encajan los componentes
- [Instalación](03-instalacion.md) — pon MIH en marcha en tu entorno
- [Guía de Administrador](04-guia-administrador.md) — configura servicios y reglas
- [API Gateway](05-api-gateway.md) — integra MIH en tu propio plugin
