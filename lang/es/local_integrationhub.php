<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cadenas de idioma en español para Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrule'] = 'Agregar regla';
$string['addservice'] = 'Agregar servicio';
$string['allcircuitsreset'] = 'Todos los circuitos de servicio han sido reiniciados.';
$string['amqp_builder'] = 'Constructor de Conexión AMQP';
$string['amqp_dlq'] = 'Dead Letter Queue (Opcional)';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_host'] = 'Host / Servidor';
$string['amqp_pass'] = 'Contraseña';
$string['amqp_port'] = 'Puerto';
$string['amqp_queue_declare'] = 'Cola a Declarar (Opcional)';
$string['amqp_queue_help'] = 'Si se define, esta Cola será declarada (creada) antes de publicar. Útil para patrones de "Work Queue".';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_routing_key_help'] = 'El Routing Key por defecto usado al publicar. Los eventos pueden sobrescribir esto con el campo "Endpoint".';
$string['amqp_user'] = 'Usuario';
$string['amqp_vhost'] = 'vHost';
$string['authtoken'] = 'Token / Credencial';
$string['authtoken_help'] = 'El token de autenticación o API key para este servicio.';
$string['authtype'] = 'Tipo de autenticación: ';
$string['authtype_apikey'] = 'API Key';
$string['authtype_bearer'] = 'Token Bearer';
$string['avglatency'] = 'Latencia prom. (ms)';
$string['base_url_help'] = 'URL base del servicio externo.';
$string['baseurl'] = 'URL Base / Conexión';
$string['cachedef_event_dedupe'] = 'Caché de deduplicación de eventos';
$string['cachedef_log_counter'] = 'Contador de inserción de logs para purga eficiente';
$string['cachedef_rate_limit'] = 'Caché de limitación de velocidad para protección de webhooks';
$string['cancel'] = 'Cancelar';
$string['cbcooldown'] = 'Enfriamiento del circuit breaker (segundos)';
$string['cbcooldown_help'] = 'Segundos de espera antes de probar el servicio de nuevo (estado half-open).';
$string['cbfailurethreshold'] = 'Umbral del circuit breaker';
$string['cbfailurethreshold_help'] = 'Número de fallos consecutivos antes de que el circuit breaker se abra.';
$string['circuit_closed'] = 'CERRADO';
$string['circuit_halfopen'] = 'SEMI-ABIERTO';
$string['circuit_open'] = 'ABIERTO';
$string['circuit_open_error'] = 'El circuit breaker de "{$a}" está ABIERTO. El servicio no está disponible.';
$string['circuitreset'] = 'El circuit breaker de "{$a}" ha sido reiniciado.';
$string['clearlogs'] = 'Borrar logs';
$string['clearlogs_confirm'] = '¿Estás seguro de que quieres borrar TODOS los logs? Esta acción no se puede deshacer.';
$string['close'] = 'Cerrar';
$string['col_actions'] = 'Acciones';
$string['col_attempts'] = 'Intentos';
$string['col_authtype'] = 'Auth';
$string['col_baseurl'] = 'URL base';
$string['col_circuit'] = 'Circuito';
$string['col_created'] = 'Creado';
$string['col_direction'] = 'Dirección';
$string['col_enabled'] = 'Estado';
$string['col_endpoint'] = 'Endpoint';
$string['col_error'] = 'Error';
$string['col_errors'] = 'Errores recientes';
$string['col_event'] = 'Evento';
$string['col_failures'] = 'Fallos';
$string['col_latency'] = 'Latencia prom.';
$string['col_latency_ms'] = 'Latencia (ms)';
$string['col_method'] = 'Método';
$string['col_name'] = 'Nombre';
$string['col_next_run'] = 'Próxima ejecución';
$string['col_service'] = 'Servicio';
$string['col_status'] = 'Estado';
$string['col_success'] = 'Resultado';
$string['col_time'] = 'Fecha/hora';
$string['col_type'] = 'Tipo';
$string['custom_headers'] = 'Encabezados personalizados (JSON)';
$string['custom_headers_forbidden'] = 'No se puede reemplazar encabezado crítico: {$a}';
$string['custom_headers_help'] = 'Encabezados HTTP personalizados opcionales como objeto JSON. Ejemplo: {"X-API-Version": "v2", "X-Client-ID": "moodle"}. Nota: No se puede reemplazar Authorization, Content-Type o Accept.';
$string['custom_headers_invalid'] = 'Formato JSON inválido para encabezados personalizados. Debe ser un objeto JSON válido.';
$string['dashboard'] = 'Panel de control';
$string['dashboard_desc'] = 'Gestiona tus integraciones con servicios externos desde el panel de Integration Hub.';
$string['default_max_retries'] = 'Reintentos máximos por defecto';
$string['default_max_retries_desc'] = 'Número máximo de reintentos por defecto para nuevos servicios.';
$string['default_timeout'] = 'Timeout por defecto (segundos)';
$string['default_timeout_desc'] = 'Tiempo de espera HTTP por defecto para nuevos servicios.';
$string['delete_dlq'] = 'Eliminar';
$string['deleteconfirm'] = '¿Estás seguro de que quieres eliminar el servicio "{$a}"? Esto también eliminará todos los logs y el estado del circuit breaker asociados.';
$string['deleteconfirmrule'] = '¿Estás seguro de que quieres eliminar esta regla?';
$string['deleteservice'] = 'Eliminar servicio';
$string['direction'] = 'Dirección';
$string['direction_inbound'] = 'Entrante';
$string['direction_outbound'] = 'Saliente';
$string['dlq'] = 'Cola de Errores (DLQ)';
$string['dlq_delete_confirm'] = '¿Estás seguro de que deseas eliminar este evento fallido?';
$string['dlq_deleted'] = 'Evento eliminado de la cola de errores.';
$string['dlq_desc'] = 'Eventos que fallaron permanentemente y requieren intervención manual.';
$string['dlq_replayed'] = 'Evento re-encolado exitosamente.';
$string['editrule'] = 'Editar regla';
$string['editservice'] = 'Editar servicio';
$string['enabled'] = 'Habilitado';
$string['error_invalid_url'] = 'Por favor, ingresa una URL válida.';
$string['error_name_exists'] = 'Ya existe un servicio con este nombre.';
$string['error_name_required'] = 'El nombre del servicio es obligatorio.';
$string['error_url_required'] = 'La URL base es obligatoria.';
$string['failed'] = 'Fallido';
$string['failure'] = 'Fallo';
$string['gotodashboard'] = 'Ir al panel de control';
$string['integrationhub:manage'] = 'Gestionar servicios de Integration Hub';
$string['integrationhub:view'] = 'Ver panel de Integration Hub';
$string['integrationstatus'] = 'Estado de integración (Histórico)';
$string['latencytrend'] = 'Tendencia de latencia (Últimas 200 peticiones)';
$string['latest_events_limit'] = 'Límite de Eventos Enviados';
$string['latest_events_limit_desc'] = 'Número de eventos recientes a mostrar en la pestaña "Eventos Enviados".';
$string['latest_events_title'] = 'Últimos {$a} Eventos Salientes';
$string['log_purge_check_frequency'] = 'Frecuencia de verificación de purga de logs';
$string['log_purge_check_frequency_desc'] = 'Verificar purga de logs cada N inserciones. Valores más altos reducen overhead pero pueden permitir que los logs crezcan temporalmente más allá del límite. Recomendado: 50-100.';
$string['logs'] = 'Logs de peticiones';
$string['logs_cleared'] = 'Todos los logs han sido borrados.';
$string['logs_cleared_service'] = 'Los registros de este servicio han sido borrados.';
$string['max_log_entries'] = 'Máximo de logs';
$string['max_log_entries_desc'] = 'Número máximo de entradas de log en la base de datos. Los registros más antiguos se eliminan automáticamente cuando se supera este límite. Ponlo a 0 para ilimitado (no recomendado).';
$string['maxretries'] = 'Reintentos máximos';
$string['no_dlq_items'] = 'No hay eventos en la cola de errores.';
$string['no_events_logged'] = 'No hay eventos salientes registrados recientemente.';
$string['no_pending_tasks'] = 'No hay tareas pendientes en la cola.';
$string['nologs'] = 'No hay peticiones registradas aún.';
$string['norules'] = 'No hay reglas de integración definidas aún.';
$string['noservices'] = 'No hay servicios registrados aún. Haz clic en "Agregar servicio" para comenzar.';
$string['orphans_purged'] = 'Se han purgado {$a} tareas huérfanas exitosamente.';
$string['payload_final'] = 'Payload Final';
$string['payload_source'] = 'Datos Origen';
$string['pending'] = 'Pendiente';
$string['pluginname'] = 'Centro de Integración';
$string['privacy:metadata'] = 'El plugin Integration Hub no almacena datos personales de usuarios. Solo registra peticiones HTTP a servicios externos.';
$string['privacy:metadata:external_services'] = 'Integration Hub envía datos de eventos de Moodle a servicios externos configurados por el administrador del sitio. Las URLs de destino exactas las define el administrador y pueden variar. Los payloads de eventos pueden incluir campos de datos personales que se enumeran a continuación.';
$string['privacy:metadata:external_services:courseid'] = 'El ID del curso asociado con el evento.';
$string['privacy:metadata:external_services:eventname'] = 'El nombre del evento de Moodle que triggero el envío.';
$string['privacy:metadata:external_services:objectid'] = 'El ID del objeto relacionado con el evento (por ejemplo, curso, tarea).';
$string['privacy:metadata:external_services:payload'] = 'El payload JSON enviado al servicio externo, que puede incluir cualquier dato de contexto del evento.';
$string['privacy:metadata:external_services:userid'] = 'El ID del usuario asociado con el evento.';
$string['purge_orphans'] = 'Purgar Huérfanas';
$string['purge_orphans_confirm'] = '¿Está seguro de que desea eliminar todas las tareas cuyas reglas han sido eliminadas?';
$string['queue'] = 'Monitor de Cola';
$string['queue_desc'] = 'Supervisa eventos pendientes y fallidos esperando ser enviados.';
$string['ratelimit_heading'] = 'Limitación de velocidad';
$string['ratelimit_heading_desc'] = 'Configurar límites de velocidad para solicitudes webhook entrantes para prevenir abusos y ataques DoS.';
$string['refreshstatus'] = 'Actualizar estado';
$string['replay'] = 'Re-enviar';
$string['resetallcircuits'] = 'Reiniciar todos los circuitos';
$string['resetcircuit'] = 'Reiniciar circuito';
$string['response_queue'] = 'Cola de respuesta';
$string['response_queue_help'] = 'Nombre de la cola AMQP para consumir mensajes de respuesta. Dejar vacío para desactivar AMQP entrante.';
$string['result_failure'] = 'FALLO';
$string['result_success'] = 'OK';
$string['retry'] = 'Reintentar';
$string['retry_now'] = 'Reintentar ahora';
$string['retrybackoff'] = 'Backoff de reintento (segundos)';
$string['retrybackoff_help'] = 'Tiempo de espera inicial entre reintentos. Se duplica con cada intento (backoff exponencial).';
$string['rule_endpoint'] = 'Sobrescribir endpoint';
$string['rule_event'] = 'Evento de Moodle';
$string['rule_event_help'] = 'Ingresa el nombre completo de la clase del evento (ej: \core\event\user_created). Puedes seleccionar de la lista o escribir uno personalizado. Nota: Los eventos personalizados también deben estar registrados en db/events.php para ser capturados.';
$string['rule_method'] = 'Método HTTP';
$string['rule_service'] = 'Servicio destino';
$string['rule_template'] = 'Plantilla de payload (JSON)';
$string['rule_template_help'] = 'Usa marcadores como {{userid}}, {{courseid}}, {{objectid}}. Déjalo vacío para enviar los datos crudos del evento.';
$string['rulecreated'] = 'Regla creada exitosamente.';
$string['ruledeleted'] = 'Regla eliminada.';
$string['rules'] = 'Reglas de integración';
$string['ruleupdated'] = 'Regla actualizada exitosamente.';
$string['saveservice'] = 'Guardar servicio';
$string['selectevent'] = 'Selecciona un evento...';
$string['selectservice'] = 'Selecciona un servicio...';
$string['sent_events'] = 'Eventos Enviados';
$string['service_disabled'] = 'El servicio "{$a}" está actualmente desactivado.';
$string['service_not_found'] = 'El servicio "{$a}" no está registrado en Integration Hub.';
$string['servicecreated'] = 'Servicio "{$a}" creado exitosamente.';
$string['servicedeleted'] = 'Servicio "{$a}" eliminado exitosamente.';
$string['servicename'] = 'Nombre de Identificación / Slug';
$string['servicename_help'] = 'Un identificador único para este servicio. Este nombre se usará en las reglas y el código. Se permiten espacios pero se recomienda alfanumérico.';
$string['services'] = 'Servicios registrados';
$string['servicetype'] = 'Tipo de Servicio';
$string['serviceupdated'] = 'Servicio "{$a}" actualizado exitosamente.';
$string['status_active'] = 'Activo';
$string['status_disabled'] = 'Desactivado';
$string['success'] = 'Éxito';
$string['task_consume_responses'] = 'Consumir mensajes de respuesta AMQP';
$string['task_delete_confirm'] = '¿Está seguro de que desea eliminar esta tarea?';
$string['task_delete_failed'] = 'Fallo al eliminar la tarea.';
$string['task_deleted'] = 'Tarea eliminada exitosamente.';
$string['task_purge_logs'] = 'Purgar logs de integración antiguos';
$string['task_retried'] = 'Tarea reprogramada para ejecución inmediata.';
$string['task_retry_failed'] = 'Fallo al reintentar la tarea.';
$string['timeout'] = 'Timeout (segundos)';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_rest'] = 'API REST';
$string['type_soap'] = 'SOAP (Legacy)';
$string['url_help_amqp'] = 'Ejemplo: amqp://user:pass@host:5672/vhost';
$string['url_help_rest'] = 'Ejemplo: https://api.service.com/v1';
$string['view_payload'] = 'Ver Payload';
$string['viewlogs'] = 'Ver logs';
$string['webhook_empty_body'] = 'Cuerpo de la petición vacío.';
$string['webhook_error'] = 'Error al procesar el webhook.';
$string['webhook_invalid_json'] = 'JSON inválido: {$a}';
$string['webhook_invalid_service'] = 'Servicio no encontrado.';
$string['webhook_invalid_token'] = 'Token de autenticación inválido.';
$string['webhook_method_not_allowed'] = 'Método no permitido. Use POST.';
$string['webhook_missing_service'] = 'Parámetro requerido faltante: service.';
$string['webhook_rate_limit'] = 'Límite de velocidad de webhook (solicitudes)';
$string['webhook_rate_limit_desc'] = 'Número máximo de solicitudes permitidas por dirección IP y servicio dentro de la ventana de tiempo.';
$string['webhook_rate_limited'] = 'Límite de velocidad excedido. Demasiadas solicitudes.';
$string['webhook_rate_window'] = 'Ventana de velocidad de webhook (segundos)';
$string['webhook_rate_window_desc'] = 'Ventana de tiempo en segundos para la limitación de velocidad (ej. 60 para límites por minuto).';
$string['webhook_received'] = 'Webhook recibido';
$string['webhook_service_disabled'] = 'El servicio está desactivado.';
$string['webhook_success'] = 'Webhook procesado correctamente.';
