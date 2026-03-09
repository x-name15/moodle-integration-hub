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

$string['addrule'] = 'Adicionar Regra';
$string['addservice'] = 'Adicionar Serviço';
$string['allcircuitsreset'] = 'Todos os circuitos de serviço foram resetados.';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_dlq'] = 'Dead Letter Queue (Opcional)';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_host'] = 'Host';
$string['amqp_pass'] = 'Password';
$string['amqp_port'] = 'Port';
$string['amqp_queue_declare'] = 'Queue a Declarar (Opcional)';
$string['amqp_queue_help'] = 'Se definido, esta Queue será declarada (criada) antes da publicação. Útil para padrões de "Work Queue".';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_routing_key_help'] = 'A Routing Key padrão usada ao publicar mensagens. Eventos podem sobrescrever isso através do campo "Endpoint".';
$string['amqp_user'] = 'User';
$string['amqp_vhost'] = 'vHost';
$string['authtoken'] = 'Token / Credencial';
$string['authtoken_help'] = 'O token de autenticação ou API key para este serviço.';
$string['authtype'] = 'Tipo de autenticação: ';
$string['authtype_apikey'] = 'API Key';
$string['authtype_bearer'] = 'Bearer Token';
$string['avglatency'] = 'Latência Média (ms)';
$string['base_url_help'] = 'URL base do serviço externo.';
$string['baseurl'] = 'URL Base / Conexão';
$string['cachedef_event_dedupe'] = 'Cache de deduplicação de eventos';
$string['cachedef_log_counter'] = 'Contador de inserção de logs para limpeza eficiente';
$string['cachedef_rate_limit'] = 'Cache de limitação de taxa para proteção de webhooks';
$string['cancel'] = 'Cancelar';
$string['cbcooldown'] = 'Cooldown do Circuit Breaker (segundos)';
$string['cbcooldown_help'] = 'Segundos a esperar antes de testar o serviço novamente (estado half-open).';
$string['cbfailurethreshold'] = 'Limiar do Circuit Breaker';
$string['cbfailurethreshold_help'] = 'Número de falhas consecutivas antes que o Circuit Breaker seja aberto.';
$string['circuit_closed'] = 'CLOSED (Fechado)';
$string['circuit_halfopen'] = 'HALF-OPEN (Semi-aberto)';
$string['circuit_open'] = 'OPEN (Aberto)';
$string['circuitreset'] = 'O Circuit Breaker para "{$a}" foi resetado.';
$string['clearlogs'] = 'Limpar Logs';
$string['clearlogs_confirm'] = 'Tem certeza de que deseja excluir TODOS os logs? Isso não pode ser desfeito.';
$string['close'] = 'Fechar';
$string['col_actions'] = 'Ações';
$string['col_attempts'] = 'Tentativas';
$string['col_authtype'] = 'Auth';
$string['col_baseurl'] = 'URL Base';
$string['col_circuit'] = 'Circuito';
$string['col_created'] = 'Criado';
$string['col_direction'] = 'Direção';
$string['col_enabled'] = 'Status';
$string['col_endpoint'] = 'Endpoint';
$string['col_error'] = 'Erro';
$string['col_errors'] = 'Erros Recentes';
$string['col_event'] = 'Evento';
$string['col_failures'] = 'Falhas';
$string['col_latency'] = 'Latência Média';
$string['col_latency_ms'] = 'Latência (ms)';
$string['col_method'] = 'Método';
$string['col_name'] = 'Nome';
$string['col_next_run'] = 'Próxima Execução';
$string['col_service'] = 'Serviço';
$string['col_status'] = 'Status';
$string['col_success'] = 'Resultado';
$string['col_time'] = 'Tempo';
$string['col_type'] = 'Tipo';
$string['custom_headers'] = 'Cabeçalhos personalizados (JSON)';
$string['custom_headers_forbidden'] = 'Não é possível substituir cabeçalho crítico: {$a}';
$string['custom_headers_help'] = 'Cabeçalhos HTTP personalizados opcionais como objeto JSON. Exemplo: {"X-API-Version": "v2", "X-Client-ID": "moodle"}. Nota: Não é possível substituir Authorization, Content-Type ou Accept.';
$string['custom_headers_invalid'] = 'Formato JSON inválido para cabeçalhos personalizados. Deve ser um objeto JSON válido.';
$string['dashboard'] = 'Dashboard';
$string['dashboard_desc'] = 'Gerencie suas integrações de serviços externos no painel do Integration Hub.';
$string['default_max_retries'] = 'Retries máximos padrão';
$string['default_max_retries_desc'] = 'Número máximo padrão de retries (tentativas) para novos serviços.';
$string['default_timeout'] = 'Timeout padrão (segundos)';
$string['default_timeout_desc'] = 'Timeout padrão para requisições HTTP para novos serviços.';
$string['delete_dlq'] = 'Excluir';
$string['deleteconfirm'] = 'Tem certeza de que deseja excluir o serviço "{$a}"? Isso também excluirá todos os logs associados e o estado do Circuit Breaker.';
$string['deleteconfirmrule'] = 'Tem certeza de que deseja excluir esta regra?';
$string['deleteservice'] = 'Excluir Serviço';
$string['direction'] = 'Direção';
$string['direction_inbound'] = 'Recebido (Inbound)';
$string['direction_outbound'] = 'Enviado (Outbound)';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_delete_confirm'] = 'Tem certeza de que deseja excluir este item da DLQ?';
$string['dlq_deleted'] = 'Evento excluído da dead letter queue.';
$string['dlq_desc'] = 'Eventos que falharam permanentemente e requerem intervenção manual.';
$string['dlq_replayed'] = 'Evento re-enfileirado com sucesso.';
$string['editrule'] = 'Editar Regra';
$string['editservice'] = 'Editar Serviço';
$string['enabled'] = 'Habilitado';
$string['error_invalid_url'] = 'Por favor insira uma URL válida.';
$string['error_name_exists'] = 'Um serviço com este nome já existe.';
$string['error_name_required'] = 'O nome do serviço é obrigatório.';
$string['error_url_required'] = 'A URL Base é obrigatória.';
$string['failed'] = 'Falhou';
$string['failure'] = 'Falha';
$string['gotodashboard'] = 'Ir para o Dashboard';
$string['integrationhub:manage'] = 'Gerenciar serviços do Integration Hub';
$string['integrationhub:view'] = 'Ver Dashboard do Integration Hub';
$string['integrationstatus'] = 'Status de Integração (Todo o período)';
$string['latencytrend'] = 'Tendência de Latência (Últimas 200 requisições)';
$string['latest_events_limit'] = 'Limite de Eventos Enviados';
$string['latest_events_limit_desc'] = 'Número de eventos recém-enviados a exibir na guia "Eventos Enviados".';
$string['latest_events_title'] = 'Últimos {$a} Eventos Enviados';
$string['log_purge_check_frequency'] = 'Frequência de verificação de limpeza de logs';
$string['log_purge_check_frequency_desc'] = 'Verificar limpeza de logs a cada N inserções. Valores mais altos reduzem overhead mas podem permitir que logs cresçam temporariamente além do limite. Recomendado: 50-100.';
$string['logs'] = 'Logs de Requisição';
$string['logs_cleared'] = 'Todos os logs foram limpos.';
$string['logs_cleared_service'] = 'Os logs para este serviço foram limpos.';
$string['max_log_entries'] = 'Máximo de entradas de log';
$string['max_log_entries_desc'] = 'Número máximo de entradas de log a manter no banco de dados. Entradas mais antigas são automaticamente excluídas quando este limite é excedido. Defina como 0 para ilimitado (não recomendado).';
$string['maxretries'] = 'Máximo de retries';
$string['no_dlq_items'] = 'Nenhum evento na dead letter queue.';
$string['no_events_logged'] = 'Nenhum evento enviado recentemente.';
$string['no_pending_tasks'] = 'Nenhuma tarefa pendente na queue.';
$string['nologs'] = 'Nenhuma requisição registrada ainda.';
$string['norules'] = 'Nenhuma regra de integração definida ainda.';
$string['noservices'] = 'Nenhum serviço registrado ainda. Clique em "Adicionar Serviço" para começar.';
$string['orphans_purged'] = '{$a} tarefas órfãs eliminadas com sucesso.';
$string['payload_final'] = 'Payload Final';
$string['payload_source'] = 'Dados de Origem';
$string['pending'] = 'Pendente';
$string['pluginname'] = 'Integration Hub';
$string['privacy:metadata'] = 'O plugin Integration Hub não armazena dados pessoais dos usuários. Ele apenas registra requisições HTTP para serviços externos.';
$string['privacy:metadata:external_services'] = 'O Integration Hub envia dados de eventos do Moodle para serviços externos configurados pelo administrador do site. Os URLs de destino exatos são definidos pelo administrador e podem variar. Os payloads dos eventos podem incluir campos de dados pessoais listados abaixo.';
$string['privacy:metadata:external_services:courseid'] = 'O ID do curso associado ao evento.';
$string['privacy:metadata:external_services:eventname'] = 'O nome do evento Moodle que acionou o envio.';
$string['privacy:metadata:external_services:objectid'] = 'O ID do objeto relacionado ao evento (por exemplo, curso, atividade).';
$string['privacy:metadata:external_services:payload'] = 'O payload JSON enviado ao serviço externo, que pode incluir qualquer dado de contexto do evento.';
$string['privacy:metadata:external_services:userid'] = 'O ID do usuário associado ao evento.';
$string['purge_orphans'] = 'Eliminar Órfãos';
$string['purge_orphans_confirm'] = 'Tem certeza de que deseja excluir todas as tarefas cujas regras foram excluídas?';
$string['queue'] = 'Monitor da Queue';
$string['queue_desc'] = 'Monitore eventos pendentes e que falharam aguardando para serem despachados.';
$string['ratelimit_heading'] = 'Limitação de taxa';
$string['ratelimit_heading_desc'] = 'Configure limites de taxa para requisições webhook de entrada para prevenir abusos e ataques DoS.';
$string['refreshstatus'] = 'Atualizar Status';
$string['replay'] = 'Replay';
$string['resetallcircuits'] = 'Resetar Todos os Circuitos';
$string['resetcircuit'] = 'Resetar Circuito';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'Nome da AMQP queue para consumir mensagens de resposta. Deixe em branco para desabilitar o AMQP de entrada.';
$string['result_failure'] = 'FALHA';
$string['result_success'] = 'OK';
$string['retry'] = 'Tentar (Retry)';
$string['retry_now'] = 'Tentar Agora';
$string['retrybackoff'] = 'Retry backoff (segundos)';
$string['retrybackoff_help'] = 'Atraso inicial de backoff para retries. Dobra com cada tentativa (backoff exponencial).';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_event'] = 'Evento Moodle';
$string['rule_event_help'] = 'Insira o nome completo da classe do evento (ex: \core\event\user_created). Você pode selecionar na lista ou digitar um personalizado. Nota: Eventos personalizados também devem ser registrados em db/events.php para serem capturados.';
$string['rule_method'] = 'Método HTTP';
$string['rule_service'] = 'Serviço de Destino';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Use placeholders como {{userid}}, {{courseid}}, {{objectid}}. Deixe em branco para enviar dados brutos do evento.';
$string['rulecreated'] = 'Regra criada com sucesso.';
$string['ruledeleted'] = 'Regra excluída.';
$string['rules'] = 'Regras de Integração';
$string['ruleupdated'] = 'Regra atualizada com sucesso.';
$string['saveservice'] = 'Salvar Serviço';
$string['selectevent'] = 'Selecione um evento...';
$string['selectservice'] = 'Selecione um serviço...';
$string['sent_events'] = 'Eventos Enviados';
$string['service_disabled'] = 'Serviço "{$a}" está atualmente desabilitado.';
$string['service_not_found'] = 'Serviço "{$a}" não está registrado no Integration Hub.';
$string['servicecreated'] = 'Serviço "{$a}" criado com sucesso.';
$string['servicedeleted'] = 'Serviço "{$a}" excluído com sucesso.';
$string['servicename'] = 'Nome de Identificação / Slug';
$string['servicename_help'] = 'Um identificador único para este serviço. Este nome será usado nas regras e no código. Espaços são permitidos, mas alfanumérico é recomendado.';
$string['services'] = 'Serviços Registrados';
$string['servicetype'] = 'Tipo de Serviço';
$string['serviceupdated'] = 'Serviço "{$a}" atualizado com sucesso.';
$string['status_active'] = 'Ativo';
$string['status_disabled'] = 'Desabilitado';
$string['success'] = 'Sucesso';
$string['task_consume_responses'] = 'Consumir mensagens de resposta AMQP';
$string['task_delete_confirm'] = 'Tem certeza de que deseja excluir esta tarefa?';
$string['task_delete_failed'] = 'Falha ao excluir a tarefa.';
$string['task_deleted'] = 'Tarefa excluída com sucesso.';
$string['task_purge_logs'] = 'Limpar logs de integração antigos';
$string['task_retried'] = 'Tarefa priorizada para execução imediata.';
$string['task_retry_failed'] = 'Falha ao tentar a tarefa.';
$string['timeout'] = 'Timeout (segundos)';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_rest'] = 'REST API';
$string['type_soap'] = 'SOAP (Legacy)';
$string['url_help_amqp'] = 'Exemplo: amqp://user:pass@host:5672/vhost';
$string['url_help_rest'] = 'Exemplo: https://api.service.com/v1';
$string['view_payload'] = 'Ver Payload';
$string['viewlogs'] = 'Ver Logs';
$string['webhook_empty_body'] = 'Corpo da requisição vazio.';
$string['webhook_error'] = 'Erro ao processar webhook.';
$string['webhook_invalid_json'] = 'JSON inválido: {$a}';
$string['webhook_invalid_service'] = 'Serviço não encontrado.';
$string['webhook_invalid_token'] = 'Token de autenticação inválido.';
$string['webhook_method_not_allowed'] = 'Método não permitido. Use POST.';
$string['webhook_missing_service'] = 'Parâmetro obrigatório ausente: service.';
$string['webhook_rate_limit'] = 'Limite de taxa de webhook (solicitações)';
$string['webhook_rate_limit_desc'] = 'Número máximo de solicitações permitidas por endereço IP e serviço dentro da janela de tempo.';
$string['webhook_rate_limited'] = 'Limite de taxa excedido. Muitas solicitações.';
$string['webhook_rate_window'] = 'Janela de taxa de webhook (segundos)';
$string['webhook_rate_window_desc'] = 'Janela de tempo em segundos para limitação de taxa (ex. 60 para limites por minuto).';
$string['webhook_received'] = 'Webhook recebido';
$string['webhook_service_disabled'] = 'O serviço está desabilitado.';
$string['webhook_success'] = 'Webhook processado com sucesso.';
