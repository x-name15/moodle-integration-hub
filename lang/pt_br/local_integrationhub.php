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
 * Portuguese (Brazil) language strings for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Integration Hub';
$string['dashboard'] = 'Dashboard';
$string['dashboard_desc'] = 'Gerencie suas integrações de serviços externos no painel do Integration Hub.';
$string['gotodashboard'] = 'Ir para o Dashboard';

// Capabilities.
$string['integrationhub:manage'] = 'Gerenciar serviços do Integration Hub';
$string['integrationhub:view'] = 'Ver Dashboard do Integration Hub';

// Settings.
$string['default_timeout'] = 'Timeout padrão (segundos)';
$string['default_timeout_desc'] = 'Timeout padrão para requisições HTTP para novos serviços.';
$string['default_max_retries'] = 'Retries máximos padrão';
$string['default_max_retries_desc'] = 'Número máximo padrão de retries (tentativas) para novos serviços.';
$string['max_log_entries'] = 'Máximo de entradas de log';
$string['max_log_entries_desc'] = 'Número máximo de entradas de log a manter no banco de dados. Entradas mais antigas são automaticamente excluídas quando este limite é excedido. Defina como 0 para ilimitado (não recomendado).';

// Dashboard.
$string['services'] = 'Serviços Registrados';
$string['noservices'] = 'Nenhum serviço registrado ainda. Clique em "Adicionar Serviço" para começar.';
$string['addservice'] = 'Adicionar Serviço';
$string['editservice'] = 'Editar Serviço';
$string['deleteservice'] = 'Excluir Serviço';
$string['deleteconfirm'] = 'Tem certeza de que deseja excluir o serviço "{$a}"? Isso também excluirá todos os logs associados e o estado do Circuit Breaker.';
$string['viewlogs'] = 'Ver Logs';
$string['refreshstatus'] = 'Atualizar Status';

// Service form.
$string['servicename'] = 'Nome de Identificação / Slug';
$string['servicename_help'] = 'Um identificador único para este serviço. Este nome será usado nas regras e no código. Espaços são permitidos, mas alfanumérico é recomendado.';
$string['resetallcircuits'] = 'Resetar Todos os Circuitos';
$string['allcircuitsreset'] = 'Todos os circuitos de serviço foram resetados.';
// Duplicate keys in EN removed for simplicity, handled conditionally later if they were exact matches.
$string['baseurl'] = 'URL Base / Conexão';
$string['base_url_help'] = 'URL base do serviço externo.';
$string['url_help_rest'] = 'Exemplo: https://api.service.com/v1';
$string['url_help_amqp'] = 'Exemplo: amqp://user:pass@host:5672/vhost';
$string['authtype'] = 'Tipo de autenticação: ';
$string['authtype_bearer'] = 'Bearer Token';
$string['authtype_apikey'] = 'API Key';
$string['authtoken'] = 'Token / Credencial';
$string['authtoken_help'] = 'O token de autenticação ou API key para este serviço.';
$string['timeout'] = 'Timeout (segundos)';
$string['maxretries'] = 'Máximo de retries';
$string['retrybackoff'] = 'Retry backoff (segundos)';
$string['retrybackoff_help'] = 'Atraso inicial de backoff para retries. Dobra com cada tentativa (backoff exponencial).';
$string['cbfailurethreshold'] = 'Limiar do Circuit Breaker';
$string['cbfailurethreshold_help'] = 'Número de falhas consecutivas antes que o Circuit Breaker seja aberto.';
$string['cbcooldown'] = 'Cooldown do Circuit Breaker (segundos)';
$string['cbcooldown_help'] = 'Segundos a esperar antes de testar o serviço novamente (estado half-open).';
$string['enabled'] = 'Habilitado';

// Service form actions.
$string['saveservice'] = 'Salvar Serviço';
$string['cancel'] = 'Cancelar';
$string['servicecreated'] = 'Serviço "{$a}" criado com sucesso.';
$string['serviceupdated'] = 'Serviço "{$a}" atualizado com sucesso.';
$string['servicedeleted'] = 'Serviço "{$a}" excluído com sucesso.';

// Table headers.
$string['col_name'] = 'Nome';
$string['col_baseurl'] = 'URL Base';
$string['col_authtype'] = 'Auth';
$string['col_circuit'] = 'Circuito';
$string['col_latency'] = 'Latência Média';
$string['col_errors'] = 'Erros Recentes';
$string['col_actions'] = 'Ações';
$string['col_enabled'] = 'Status';
$string['status_active'] = 'Ativo';
$string['status_disabled'] = 'Desabilitado';

// Circuit states.
$string['circuit_closed'] = 'CLOSED (Fechado)';
$string['circuit_open'] = 'OPEN (Aberto)';
$string['circuit_halfopen'] = 'HALF-OPEN (Semi-aberto)';
$string['resetcircuit'] = 'Resetar Circuito';
$string['circuitreset'] = 'O Circuit Breaker para "{$a}" foi resetado.';

// Errors.
$string['service_not_found'] = 'Serviço "{$a}" não está registrado no Integration Hub.';
$string['service_disabled'] = 'Serviço "{$a}" está atualmente desabilitado.';
// string reuse for 'circuit_open_error' vs state 'circuit_open', Moodle handles last array entry. Let's make sure we map exact EN keys.
$string['error_name_exists'] = 'Um serviço com este nome já existe.';
$string['error_invalid_url'] = 'Por favor insira uma URL válida.';
$string['error_name_required'] = 'O nome do serviço é obrigatório.';
$string['error_url_required'] = 'A URL Base é obrigatória.';

// Logs.
$string['logs'] = 'Logs de Requisição';
$string['nologs'] = 'Nenhuma requisição registrada ainda.';
$string['col_endpoint'] = 'Endpoint';
$string['col_method'] = 'Método';
$string['col_status'] = 'Status';
$string['col_latency_ms'] = 'Latência (ms)';
$string['col_attempts'] = 'Tentativas';
$string['col_success'] = 'Resultado';
$string['col_error'] = 'Erro';
$string['col_time'] = 'Tempo';
$string['result_success'] = 'OK';
$string['result_failure'] = 'FALHA';
$string['clearlogs'] = 'Limpar Logs';
$string['clearlogs_confirm'] = 'Tem certeza de que deseja excluir TODOS os logs? Isso não pode ser desfeito.';
$string['logs_cleared'] = 'Todos os logs foram limpos.';
$string['logs_cleared_service'] = 'Os logs para este serviço foram limpos.';

// Privacy.
// En caso de pt_br, no obligaron traduccion de text técnico o legal en las pautas, pero la traducimos.
$string['privacy:metadata'] = 'O plugin Integration Hub não armazena dados pessoais dos usuários. Ele apenas registra requisições HTTP para serviços externos.';

// Event Rules.
$string['rules'] = 'Regras de Integração';
$string['addrule'] = 'Adicionar Regra';
$string['editrule'] = 'Editar Regra';
$string['rule_event'] = 'Evento Moodle';
$string['rule_event_help'] = 'Insira o nome completo da classe do evento (ex: \core\event\user_created). Você pode selecionar na lista ou digitar um personalizado. Nota: Eventos personalizados também devem ser registrados em db/events.php para serem capturados.';
$string['rule_service'] = 'Serviço de Destino';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_method'] = 'Método HTTP';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Use placeholders como {{userid}}, {{courseid}}, {{objectid}}. Deixe em branco para enviar dados brutos do evento.';
$string['selectevent'] = 'Selecione um evento...';
$string['selectservice'] = 'Selecione um serviço...';
$string['rulecreated'] = 'Regra criada com sucesso.';
$string['ruleupdated'] = 'Regra atualizada com sucesso.';
$string['ruledeleted'] = 'Regra excluída.';
$string['deleteconfirmrule'] = 'Tem certeza de que deseja excluir esta regra?';
$string['col_type'] = 'Tipo';
$string['col_event'] = 'Evento';
$string['col_service'] = 'Serviço';
$string['col_endpoint'] = 'Endpoint';
// col_enabled is reused
$string['norules'] = 'Nenhuma regra de integração definida ainda.';
$string['servicetype'] = 'Tipo de Serviço';
$string['type_rest'] = 'REST API';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_soap'] = 'SOAP (Legacy)';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_host'] = 'Host';
$string['amqp_port'] = 'Port';
$string['amqp_user'] = 'User';
$string['amqp_pass'] = 'Password';
$string['amqp_vhost'] = 'vHost';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_queue_declare'] = 'Queue a Declarar (Opcional)';
$string['amqp_routing_key_help'] = 'A Routing Key padrão usada ao publicar mensagens. Eventos podem sobrescrever isso através do campo "Endpoint".';
$string['amqp_queue_help'] = 'Se definido, esta Queue será declarada (criada) antes da publicação. Útil para padrões de "Work Queue".';
$string['amqp_dlq'] = 'Dead Letter Queue (Opcional)';

// Queue.
$string['queue'] = 'Monitor da Queue';
$string['queue_desc'] = 'Monitore eventos pendentes e que falharam aguardando para serem despachados.';
$string['no_pending_tasks'] = 'Nenhuma tarefa pendente na queue.';
$string['col_failures'] = 'Falhas';
$string['col_next_run'] = 'Próxima Execução';
$string['col_created'] = 'Criado';
$string['retry_now'] = 'Tentar Agora';
$string['failed'] = 'Falhou';
$string['retry'] = 'Tentar (Retry)';
$string['pending'] = 'Pendente';
$string['task_retried'] = 'Tarefa priorizada para execução imediata.';
$string['task_retry_failed'] = 'Falha ao tentar a tarefa.';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_desc'] = 'Eventos que falharam permanentemente e requerem intervenção manual.';
// col_error is reused
$string['replay'] = 'Replay';
$string['delete_dlq'] = 'Excluir';
$string['no_dlq_items'] = 'Nenhum evento na dead letter queue.';
$string['dlq_replayed'] = 'Evento re-enfileirado com sucesso.';
$string['dlq_deleted'] = 'Evento excluído da dead letter queue.';
$string['task_deleted'] = 'Tarefa excluída com sucesso.';
$string['task_delete_failed'] = 'Falha ao excluir a tarefa.';
$string['orphans_purged'] = '{$a} tarefas órfãs eliminadas com sucesso.';
$string['purge_orphans'] = 'Eliminar Órfãos';
$string['purge_orphans_confirm'] = 'Tem certeza de que deseja excluir todas as tarefas cujas regras foram excluídas?';
$string['task_delete_confirm'] = 'Tem certeza de que deseja excluir esta tarefa?';
$string['dlq_delete_confirm'] = 'Tem certeza de que deseja excluir este item da DLQ?';

// Queue Payload Viewer
$string['view_payload'] = 'Ver Payload';
$string['payload_source'] = 'Dados de Origem';
$string['payload_final'] = 'Payload Final';
$string['close'] = 'Fechar';

// Dashboard Charts.
$string['integrationstatus'] = 'Status de Integração (Todo o período)';
$string['latencytrend'] = 'Tendência de Latência (Últimas 200 requisições)';
$string['success'] = 'Sucesso';
$string['failure'] = 'Falha';
$string['avglatency'] = 'Latência Média (ms)';

// Webhook & Bidirectional.
$string['webhook_received'] = 'Webhook recebido';
$string['webhook_invalid_token'] = 'Token de autenticação inválido.';
$string['webhook_invalid_service'] = 'Serviço não encontrado.';
$string['webhook_success'] = 'Webhook processado com sucesso.';
$string['webhook_error'] = 'Erro ao processar webhook.';
$string['direction'] = 'Direção';
$string['direction_outbound'] = 'Enviado (Outbound)';
$string['direction_inbound'] = 'Recebido (Inbound)';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'Nome da AMQP queue para consumir mensagens de resposta. Deixe em branco para desabilitar o AMQP de entrada.';
$string['task_consume_responses'] = 'Consumir mensagens de resposta AMQP';
$string['col_direction'] = 'Direção';

// Sent Events Page.
$string['sent_events'] = 'Eventos Enviados';
$string['latest_events_title'] = 'Últimos {$a} Eventos Enviados';
$string['latest_events_limit'] = 'Limite de Eventos Enviados';
$string['latest_events_limit_desc'] = 'Número de eventos recém-enviados a exibir na guia "Eventos Enviados".';
$string['no_events_logged'] = 'Nenhum evento enviado recentemente.';
