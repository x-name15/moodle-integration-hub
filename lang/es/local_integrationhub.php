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

// General.
$string['pluginname'] = 'Centro de Integración';
$string['dashboard'] = 'Panel de control';
$string['dashboard_desc'] = 'Gestiona tus integraciones con servicios externos desde el panel de Integration Hub.';
$string['gotodashboard'] = 'Ir al panel de control';

// Capabilities.
$string['integrationhub:manage'] = 'Gestionar servicios de Integration Hub';
$string['integrationhub:view'] = 'Ver panel de Integration Hub';

// Settings.
$string['default_timeout'] = 'Timeout por defecto (segundos)';
$string['default_timeout_desc'] = 'Tiempo de espera HTTP por defecto para nuevos servicios.';
$string['default_max_retries'] = 'Reintentos máximos por defecto';
$string['default_max_retries_desc'] = 'Número máximo de reintentos por defecto para nuevos servicios.';
$string['max_log_entries'] = 'Máximo de logs';
$string['max_log_entries_desc'] = 'Número máximo de entradas de log en la base de datos. Los registros más antiguos se eliminan automáticamente cuando se supera este límite. Ponlo a 0 para ilimitado (no recomendado).';

// Dashboard.
$string['services'] = 'Servicios registrados';
$string['noservices'] = 'No hay servicios registrados aún. Haz clic en "Agregar servicio" para comenzar.';
$string['addservice'] = 'Agregar servicio';
$string['editservice'] = 'Editar servicio';
$string['deleteservice'] = 'Eliminar servicio';
$string['deleteconfirm'] = '¿Estás seguro de que quieres eliminar el servicio "{$a}"? Esto también eliminará todos los logs y el estado del circuit breaker asociados.';
$string['viewlogs'] = 'Ver logs';
$string['refreshstatus'] = 'Actualizar estado';

// Service form.
$string['servicename'] = 'Nombre de Identificación / Slug';
$string['servicename_help'] = 'Un identificador único para este servicio. Este nombre se usará en las reglas y el código. Se permiten espacios pero se recomienda alfanumérico.';
$string['resetallcircuits'] = 'Reiniciar todos los circuitos';
$string['allcircuitsreset'] = 'Todos los circuitos de servicio han sido reiniciados.';
$string['resetcircuit'] = 'Reiniciar circuito';
$string['circuitreset'] = 'El circuit breaker de "{$a}" ha sido reiniciado.';
$string['baseurl'] = 'URL Base / Conexión';
$string['base_url_help'] = 'URL base del servicio externo.';
$string['url_help_rest'] = 'Ejemplo: https://api.service.com/v1';
$string['url_help_amqp'] = 'Ejemplo: amqp://user:pass@host:5672/vhost';
$string['authtype'] = 'Tipo de autenticación: ';
$string['authtype_bearer'] = 'Token Bearer';
$string['authtype_apikey'] = 'API Key';
$string['authtoken'] = 'Token / Credencial';
$string['authtoken_help'] = 'El token de autenticación o API key para este servicio.';
$string['timeout'] = 'Timeout (segundos)';
$string['maxretries'] = 'Reintentos máximos';
$string['retrybackoff'] = 'Backoff de reintento (segundos)';
$string['retrybackoff_help'] = 'Tiempo de espera inicial entre reintentos. Se duplica con cada intento (backoff exponencial).';
$string['cbfailurethreshold'] = 'Umbral del circuit breaker';
$string['cbfailurethreshold_help'] = 'Número de fallos consecutivos antes de que el circuit breaker se abra.';
$string['cbcooldown'] = 'Enfriamiento del circuit breaker (segundos)';
$string['cbcooldown_help'] = 'Segundos de espera antes de probar el servicio de nuevo (estado half-open).';
$string['enabled'] = 'Habilitado';

// Service form actions.
$string['saveservice'] = 'Guardar servicio';
$string['cancel'] = 'Cancelar';
$string['servicecreated'] = 'Servicio "{$a}" creado exitosamente.';
$string['serviceupdated'] = 'Servicio "{$a}" actualizado exitosamente.';
$string['servicedeleted'] = 'Servicio "{$a}" eliminado exitosamente.';

// Table headers.
$string['col_name'] = 'Nombre';
$string['col_baseurl'] = 'URL base';
$string['col_authtype'] = 'Auth';
$string['col_circuit'] = 'Circuito';
$string['col_latency'] = 'Latencia prom.';
$string['col_errors'] = 'Errores recientes';
$string['col_actions'] = 'Acciones';
$string['col_enabled'] = 'Estado';
$string['status_active'] = 'Activo';
$string['status_disabled'] = 'Desactivado';

// Circuit states.
$string['circuit_closed'] = 'CERRADO';
$string['circuit_open'] = 'ABIERTO';
$string['circuit_halfopen'] = 'SEMI-ABIERTO';
$string['resetcircuit'] = 'Reiniciar circuito';
$string['circuitreset'] = 'El circuit breaker de "{$a}" ha sido reiniciado.';

// Errors.
$string['service_not_found'] = 'El servicio "{$a}" no está registrado en Integration Hub.';
$string['service_disabled'] = 'El servicio "{$a}" está actualmente desactivado.';
$string['circuit_open'] = 'El circuit breaker de "{$a}" está ABIERTO. El servicio no está disponible.';
$string['error_name_exists'] = 'Ya existe un servicio con este nombre.';
$string['error_invalid_url'] = 'Por favor, ingresa una URL válida.';
$string['error_name_required'] = 'El nombre del servicio es obligatorio.';
$string['error_url_required'] = 'La URL base es obligatoria.';

// Logs.
$string['logs'] = 'Logs de peticiones';
$string['nologs'] = 'No hay peticiones registradas aún.';
$string['col_endpoint'] = 'Endpoint';
$string['col_method'] = 'Método';
$string['col_status'] = 'Estado';
$string['col_latency_ms'] = 'Latencia (ms)';
$string['col_attempts'] = 'Intentos';
$string['col_success'] = 'Resultado';
$string['col_error'] = 'Error';
$string['col_time'] = 'Fecha/hora';
$string['result_success'] = 'OK';
$string['result_failure'] = 'FALLO';
$string['clearlogs'] = 'Borrar logs';
$string['clearlogs_confirm'] = '¿Estás seguro de que quieres borrar TODOS los logs? Esta acción no se puede deshacer.';
$string['logs_cleared'] = 'Todos los logs han sido borrados.';
$string['logs_cleared_service'] = 'Los registros de este servicio han sido borrados.';

// Privacy.
$string['privacy:metadata'] = 'El plugin Integration Hub no almacena datos personales de usuarios. Solo registra peticiones HTTP a servicios externos.';

// Reglas de Eventos.
$string['rules'] = 'Reglas de integración';
$string['addrule'] = 'Agregar regla';
$string['editrule'] = 'Editar regla';
$string['rule_event'] = 'Evento de Moodle';
$string['rule_event_help'] = 'Ingresa el nombre completo de la clase del evento (ej: \core\event\user_created). Puedes seleccionar de la lista o escribir uno personalizado. Nota: Los eventos personalizados también deben estar registrados en db/events.php para ser capturados.';
$string['rule_service'] = 'Servicio destino';
$string['rule_endpoint'] = 'Sobrescribir endpoint';
$string['rule_method'] = 'Método HTTP';
$string['rule_template'] = 'Plantilla de payload (JSON)';
$string['rule_template_help'] = 'Usa marcadores como {{userid}}, {{courseid}}, {{objectid}}. Déjalo vacío para enviar los datos crudos del evento.';
$string['selectevent'] = 'Selecciona un evento...';
$string['selectservice'] = 'Selecciona un servicio...';
$string['rulecreated'] = 'Regla creada exitosamente.';
$string['ruleupdated'] = 'Regla actualizada exitosamente.';
$string['ruledeleted'] = 'Regla eliminada.';
$string['deleteconfirmrule'] = '¿Estás seguro de que quieres eliminar esta regla?';
$string['col_type'] = 'Tipo';
$string['col_event'] = 'Evento';
$string['col_service'] = 'Servicio';
$string['col_endpoint'] = 'Endpoint';
$string['col_enabled'] = 'Estado';
$string['norules'] = 'No hay reglas de integración definidas aún.';
$string['servicetype'] = 'Tipo de Servicio';
$string['type_rest'] = 'API REST';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_soap'] = 'SOAP (Legacy)';
$string['amqp_builder'] = 'Constructor de Conexión AMQP';
$string['amqp_host'] = 'Host / Servidor';
$string['amqp_port'] = 'Puerto';
$string['amqp_user'] = 'Usuario';
$string['amqp_pass'] = 'Contraseña';
$string['amqp_vhost'] = 'vHost';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_queue_declare'] = 'Cola a Declarar (Opcional)';
$string['amqp_routing_key_help'] = 'El Routing Key por defecto usado al publicar. Los eventos pueden sobrescribir esto con el campo "Endpoint".';
$string['amqp_queue_help'] = 'Si se define, esta Cola será declarada (creada) antes de publicar. Útil para patrones de "Work Queue".';
$string['amqp_dlq'] = 'Dead Letter Queue (Opcional)';

// Queue.
$string['queue'] = 'Monitor de Cola';
$string['queue_desc'] = 'Supervisa eventos pendientes y fallidos esperando ser enviados.';
$string['no_pending_tasks'] = 'No hay tareas pendientes en la cola.';
$string['col_failures'] = 'Fallos';
$string['col_next_run'] = 'Próxima ejecución';
$string['col_created'] = 'Creado';
$string['retry_now'] = 'Reintentar ahora';
$string['failed'] = 'Fallido';
$string['retry'] = 'Reintentar';
$string['pending'] = 'Pendiente';
$string['task_retried'] = 'Tarea reprogramada para ejecución inmediata.';
$string['task_retry_failed'] = 'Fallo al reintentar la tarea.';
$string['dlq'] = 'Cola de Errores (DLQ)';
$string['dlq_desc'] = 'Eventos que fallaron permanentemente y requieren intervención manual.';
$string['col_error'] = 'Error';
$string['replay'] = 'Re-enviar';
$string['delete_dlq'] = 'Eliminar';
$string['no_dlq_items'] = 'No hay eventos en la cola de errores.';
$string['dlq_replayed'] = 'Evento re-encolado exitosamente.';
$string['dlq_deleted'] = 'Evento eliminado de la cola de errores.';
$string['task_deleted'] = 'Tarea eliminada exitosamente.';
$string['task_delete_failed'] = 'Fallo al eliminar la tarea.';
$string['orphans_purged'] = 'Se han purgado {$a} tareas huérfanas exitosamente.';
$string['purge_orphans'] = 'Purgar Huérfanas';
$string['purge_orphans_confirm'] = '¿Está seguro de que desea eliminar todas las tareas cuyas reglas han sido eliminadas?';
$string['task_delete_confirm'] = '¿Está seguro de que desea eliminar esta tarea?';
$string['dlq_delete_confirm'] = '¿Estás seguro de que deseas eliminar este evento fallido?';

// Queue Payload Viewer
$string['view_payload'] = 'Ver Payload';
$string['payload_source'] = 'Datos Origen';
$string['payload_final'] = 'Payload Final';
$string['close'] = 'Cerrar';

// Dashboard Charts.
$string['integrationstatus'] = 'Estado de integración (Histórico)';
$string['latencytrend'] = 'Tendencia de latencia (Últimas 200 peticiones)';
$string['success'] = 'Éxito';
$string['failure'] = 'Fallo';
$string['avglatency'] = 'Latencia prom. (ms)';

// Webhook & Bidireccional.
$string['webhook_received'] = 'Webhook recibido';
$string['webhook_invalid_token'] = 'Token de autenticación inválido.';
$string['webhook_invalid_service'] = 'Servicio no encontrado.';
$string['webhook_success'] = 'Webhook procesado correctamente.';
$string['webhook_error'] = 'Error al procesar el webhook.';
$string['direction'] = 'Dirección';
$string['direction_outbound'] = 'Saliente';
$string['direction_inbound'] = 'Entrante';
$string['response_queue'] = 'Cola de respuesta';
$string['response_queue_help'] = 'Nombre de la cola AMQP para consumir mensajes de respuesta. Dejar vacío para desactivar AMQP entrante.';
$string['task_consume_responses'] = 'Consumir mensajes de respuesta AMQP';
$string['col_direction'] = 'Dirección';

// Sent Events Page.
$string['sent_events'] = 'Eventos Enviados';
$string['latest_events_title'] = 'Últimos {$a} Eventos Salientes';
$string['latest_events_limit'] = 'Límite de Eventos Enviados';
$string['latest_events_limit_desc'] = 'Número de eventos recientes a mostrar en la pestaña "Eventos Enviados".';
$string['no_events_logged'] = 'No hay eventos salientes registrados recientemente.';