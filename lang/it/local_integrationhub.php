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
 * Italian language strings for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrule'] = 'Aggiungi Regola';
$string['addservice'] = 'Aggiungi Servizio';
$string['allcircuitsreset'] = 'Tutti i circuiti dei servizi sono stati reimpostati.';
$string['amqp_builder'] = 'Costruttore di Connessione AMQP';
$string['amqp_dlq'] = 'Dead Letter Queue (Opzionale)';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_host'] = 'Host';
$string['amqp_pass'] = 'Password';
$string['amqp_port'] = 'Port';
$string['amqp_queue_declare'] = 'Queue da dichiarare (Opzionale)';
$string['amqp_queue_help'] = 'Se impostata, questa Queue sarà dichiarata (creata) prima della pubblicazione dei messaggi. Utile per modelli "Work Queue".';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_routing_key_help'] = 'La Routing Key predefinita utilizzata durante la pubblicazione dei messaggi. Gli eventi possono sovrascriverla tramite il campo "Endpoint".';
$string['amqp_user'] = 'User';
$string['amqp_vhost'] = 'vHost';
$string['authtoken'] = 'Token / Credenziale';
$string['authtoken_help'] = 'Token di autenticazione o chiave API (API Key) per questo servizio.';
$string['authtype'] = 'Tipo di autenticazione: ';
$string['authtype_apikey'] = 'API Key';
$string['authtype_bearer'] = 'Bearer Token';
$string['avglatency'] = 'Latenza media (ms)';
$string['base_url_help'] = 'URL base del servizio esterno.';
$string['baseurl'] = 'URL Base / Connessione';
$string['cachedef_event_dedupe'] = 'Cache di deduplicazione degli eventi';
$string['cachedef_log_counter'] = 'Contatore di inserimento log per eliminazione efficiente';
$string['cachedef_rate_limit'] = 'Cache di limitazione della velocità per la protezione dei webhook';
$string['cancel'] = 'Annulla';
$string['cbcooldown'] = 'Cooldown del Circuit Breaker (secondi)';
$string['cbcooldown_help'] = 'Secondi di attesa prima di testare nuovamente il servizio (stato half-open).';
$string['cbfailurethreshold'] = 'Soglia del Circuit Breaker';
$string['cbfailurethreshold_help'] = 'Numero di errori consecutivi prima dell\'apertura del Circuit Breaker.';
$string['circuit_closed'] = 'CLOSED';
$string['circuit_halfopen'] = 'HALF-OPEN';
$string['circuit_open'] = 'OPEN';
$string['circuitreset'] = 'Il Circuit Breaker per "{$a}" è stato reimpostato.';
$string['clearlogs'] = 'Pulisci Log';
$string['clearlogs_confirm'] = 'Sei sicuro di voler eliminare TUTTI i log? L\'azione non può essere annullata.';
$string['close'] = 'Chiudi';
$string['col_actions'] = 'Azioni';
$string['col_attempts'] = 'Tentativi';
$string['col_authtype'] = 'Auth';
$string['col_baseurl'] = 'URL Base';
$string['col_circuit'] = 'Circuito';
$string['col_created'] = 'Creato';
$string['col_direction'] = 'Direzione';
$string['col_enabled'] = 'Stato';
$string['col_endpoint'] = 'Endpoint';
$string['col_error'] = 'Errore';
$string['col_errors'] = 'Errori Recenti';
$string['col_event'] = 'Evento';
$string['col_failures'] = 'Fallimenti';
$string['col_latency'] = 'Latenza Media';
$string['col_latency_ms'] = 'Latenza (ms)';
$string['col_method'] = 'Metodo';
$string['col_name'] = 'Nome';
$string['col_next_run'] = 'Prossima Esecuzione';
$string['col_service'] = 'Servizio';
$string['col_status'] = 'Stato';
$string['col_success'] = 'Risultato';
$string['col_time'] = 'Tempo';
$string['col_type'] = 'Tipo';
$string['custom_headers'] = 'Intestazioni personalizzate (JSON)';
$string['custom_headers_forbidden'] = 'Impossibile sovrascrivere intestazione critica: {$a}';
$string['custom_headers_help'] = 'Intestazioni HTTP personalizzate opzionali come oggetto JSON. Esempio: {"X-API-Version": "v2", "X-Client-ID": "moodle"}. Nota: Impossibile sovrascrivere Authorization, Content-Type o Accept.';
$string['custom_headers_invalid'] = 'Formato JSON non valido per intestazioni personalizzate. Deve essere un oggetto JSON valido.';
$string['dashboard'] = 'Dashboard';
$string['dashboard_desc'] = 'Gestisci le integrazioni dei tuoi servizi esterni dalla dashboard di Integration Hub.';
$string['default_max_retries'] = 'Retries massimi predefiniti';
$string['default_max_retries_desc'] = 'Numero massimo di retries (tentativi) per i nuovi servizi.';
$string['default_timeout'] = 'Timeout predefinito (secondi)';
$string['default_timeout_desc'] = 'Timeout predefinito per le richieste HTTP verso i nuovi servizi.';
$string['delete_dlq'] = 'Elimina';
$string['deleteconfirm'] = 'Sei sicuro di voler eliminare il servizio "{$a}"? Questo cancellerà anche i log associati e lo stato del Circuit Breaker.';
$string['deleteconfirmrule'] = 'Sei sicuro di voler eliminare questa regola?';
$string['deleteservice'] = 'Elimina Servizio';
$string['direction'] = 'Direzione';
$string['direction_inbound'] = 'In Entrata';
$string['direction_outbound'] = 'In Uscita';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_delete_confirm'] = 'Sei sicuro di voler eliminare questo elemento dalla DLQ?';
$string['dlq_deleted'] = 'Evento eliminato dalla dead letter queue.';
$string['dlq_desc'] = 'Eventi falliti in via definitiva che necessitano di un intervento manuale.';
$string['dlq_replayed'] = 'Evento ri-accodato con successo.';
$string['editrule'] = 'Modifica Regola';
$string['editservice'] = 'Modifica Servizio';
$string['enabled'] = 'Abilitato';
$string['error_invalid_url'] = 'Inserisci un URL valido.';
$string['error_name_exists'] = 'Esiste già un servizio con questo nome.';
$string['error_name_required'] = 'Il nome del servizio è obbligatorio.';
$string['error_url_required'] = 'L\'URL di base è obbligatorio.';
$string['failed'] = 'Fallito';
$string['failure'] = 'Errore';
$string['gotodashboard'] = 'Vai alla Dashboard';
$string['integrationhub:manage'] = 'Gestisci i servizi Integration Hub';
$string['integrationhub:view'] = 'Visualizza la dashboard di Integration Hub';
$string['integrationstatus'] = 'Stato dell\'Integrazione (Tutto il tempo)';
$string['latencytrend'] = 'Tendenza della Latenza (Ultime 200 rich.)';
$string['latest_events_limit'] = 'Limite Eventi Inviati';
$string['latest_events_limit_desc'] = 'Numero di eventi recenti da mostrare nella scheda "Eventi Inviati".';
$string['latest_events_title'] = 'Ultimi {$a} Eventi in Uscita';
$string['log_purge_check_frequency'] = 'Frequenza di verifica eliminazione log';
$string['log_purge_check_frequency_desc'] = 'Verifica eliminazione log ogni N inserimenti. Valori più alti riducono l\'overhead ma possono permettere ai log di crescere temporaneamente oltre il limite. Raccomandato: 50-100.';
$string['logs'] = 'Registro Richieste';
$string['logs_cleared'] = 'Tutti i log sono stati puliti.';
$string['logs_cleared_service'] = 'I log di questo servizio sono stati puliti.';
$string['max_log_entries'] = 'Numero massimo di record nel log';
$string['max_log_entries_desc'] = 'Numero massimo di record di log da mantenere nel database. Le voci più vecchie vengono eliminate automaticamente quando viene superato questo limite. Impostare su 0 per quantità illimitata (non consigliato).';
$string['maxretries'] = 'Max retries';
$string['no_dlq_items'] = 'Nessun evento nella dead letter queue.';
$string['no_events_logged'] = 'Nessun evento inviato di recente.';
$string['no_pending_tasks'] = 'Nessuna operazione in coda.';
$string['nologs'] = 'Ancora nessuna richiesta registrata nel log.';
$string['norules'] = 'Ancora nessuna regola di integrazione definita.';
$string['noservices'] = 'Ancora nessun servizio registrato. Clicca su "Aggiungi Servizio" per iniziare.';
$string['orphans_purged'] = '{$a} operazioni orfane rimosse con successo.';
$string['payload_final'] = 'Payload Finale';
$string['payload_source'] = 'Dati di Origine';
$string['pending'] = 'In Attesa';
$string['pluginname'] = 'Integration Hub';
$string['privacy:metadata'] = 'Il plugin Integration Hub non archivia dati personali degli utenti. Registra solo richieste HTTP verso servizi esterni ai fini di monitoraggio tecnico.';
$string['privacy:metadata:external_services'] = 'Integration Hub invia i dati degli eventi Moodle a servizi esterni configurati dall\'amministratore del sito. Gli URL di destinazione esatti sono definiti dall\'amministratore e possono variare. I payload degli eventi possono contenere campi di dati personali elencati di seguito.';
$string['privacy:metadata:external_services:courseid'] = 'L\'ID del corso associato all\'evento.';
$string['privacy:metadata:external_services:eventname'] = 'Il nome dell\'evento Moodle che ha attivato l\'invio.';
$string['privacy:metadata:external_services:objectid'] = 'L\'ID dell\'oggetto correlato all\'evento (ad es. corso, compito).';
$string['privacy:metadata:external_services:payload'] = 'Il payload JSON inviato al servizio esterno, che può includere qualsiasi dato di contesto dell\'evento.';
$string['privacy:metadata:external_services:userid'] = 'L\'ID dell\'utente associato all\'evento.';
$string['purge_orphans'] = 'Pulisci Orfani';
$string['purge_orphans_confirm'] = 'Sei sicuro di voler rimuovere tutte le operazioni le cui regole sono state eliminate?';
$string['queue'] = 'Monitoraggio della Queue';
$string['queue_desc'] = 'Monitora gli eventi pendenti o falliti che attendono di essere dispacciati.';
$string['ratelimit_heading'] = 'Limitazione di velocità';
$string['ratelimit_heading_desc'] = 'Configura limiti di velocità per le richieste webhook in entrata per prevenire abusi e attacchi DoS.';
$string['refreshstatus'] = 'Aggiorna Stato';
$string['replay'] = 'Replay';
$string['resetallcircuits'] = 'Reimposta tutti i circuiti';
$string['resetcircuit'] = 'Reimposta Circuito';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'Nome della queue AMQP da cui ricevere messaggi di risposta. Lascia vuoto per disabilitare AMQP in entrata.';
$string['result_failure'] = 'FALLITO';
$string['result_success'] = 'OK';
$string['retry'] = 'Retry';
$string['retry_now'] = 'Riprova Ora';
$string['retrybackoff'] = 'Retry backoff (secondi)';
$string['retrybackoff_help'] = 'Ritardo di backoff iniziale per i retry. Raddoppia a ogni tentativo (backoff esponenziale).';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_event'] = 'Evento Moodle';
$string['rule_event_help'] = 'Inserisci il nome completo della classe dell\'evento (es. \core\event\user_created). Puoi selezionarne uno dall\'elenco o digitarne uno personalizzato. Nota: gli eventi personalizzati devono anche essere registrati in db/events.php per essere catturati.';
$string['rule_method'] = 'Metodo HTTP';
$string['rule_service'] = 'Servizio di Destinazione';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Usa segnaposti come {{userid}}, {{courseid}}, {{objectid}}. Lascia vuoto per inviare i dati grezzi dell\'evento.';
$string['rulecreated'] = 'Regola creata con successo.';
$string['ruledeleted'] = 'Regola eliminata.';
$string['rules'] = 'Regole di Integrazione';
$string['ruleupdated'] = 'Regola aggiornata con successo.';
$string['saveservice'] = 'Salva Servizio';
$string['selectevent'] = 'Seleziona un evento...';
$string['selectservice'] = 'Seleziona un servizio...';
$string['sent_events'] = 'Eventi Inviati';
$string['service_disabled'] = 'Il servizio "{$a}" è attualmente disabilitato.';
$string['service_not_found'] = 'Il servizio "{$a}" non è registrato nell\'Integration Hub.';
$string['servicecreated'] = 'Servizio "{$a}" creato con successo.';
$string['servicedeleted'] = 'Servizio "{$a}" eliminato con successo.';
$string['servicename'] = 'Nome Identificativo / Slug';
$string['servicename_help'] = 'Identificatore univoco per questo servizio. Questo nome sarà utilizzato nelle regole e nel codice. Sono ammessi gli spazi ma è consigliabile usare caratteri alfanumerici.';
$string['services'] = 'Servizi Registrati';
$string['servicetype'] = 'Tipo di Servizio';
$string['serviceupdated'] = 'Servizio "{$a}" aggiornato con successo.';
$string['status_active'] = 'Attivo';
$string['status_disabled'] = 'Disabilitato';
$string['success'] = 'Successo';
$string['task_consume_responses'] = 'Ricevere messaggi di risposta AMQP';
$string['task_delete_confirm'] = 'Sei sicuro di voler eliminare questa operazione?';
$string['task_delete_failed'] = 'Impossibile eliminare l\'operazione.';
$string['task_deleted'] = 'Operazione eliminata con successo.';
$string['task_purge_logs'] = 'Elimina vecchi log di integrazione';
$string['task_retried'] = 'Priorità operazione ripristinata per esecuzione immediata.';
$string['task_retry_failed'] = 'Tentativo operazione fallito.';
$string['timeout'] = 'Timeout (secondi)';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_rest'] = 'REST API';
$string['type_soap'] = 'SOAP (Legacy)';
$string['url_help_amqp'] = 'Esempio: amqp://user:pass@host:5672/vhost';
$string['url_help_rest'] = 'Esempio: https://api.service.com/v1';
$string['view_payload'] = 'Visualizza Payload';
$string['viewlogs'] = 'Visualizza Log';
$string['webhook_empty_body'] = 'Corpo della richiesta vuoto.';
$string['webhook_error'] = 'Errore nell\'elaborazione del webhook.';
$string['webhook_invalid_json'] = 'JSON non valido: {$a}';
$string['webhook_invalid_service'] = 'Servizio non trovato.';
$string['webhook_invalid_token'] = 'Token di autenticazione non valido.';
$string['webhook_method_not_allowed'] = 'Metodo non consentito. Usare POST.';
$string['webhook_missing_service'] = 'Parametro obbligatorio mancante: service.';
$string['webhook_rate_limit'] = 'Limite di velocità webhook (richieste)';
$string['webhook_rate_limit_desc'] = 'Numero massimo di richieste consentite per indirizzo IP e servizio entro la finestra temporale.';
$string['webhook_rate_limited'] = 'Limite di velocità superato. Troppe richieste.';
$string['webhook_rate_window'] = 'Finestra di velocità webhook (secondi)';
$string['webhook_rate_window_desc'] = 'Finestra temporale in secondi per la limitazione della velocità (es. 60 per limiti al minuto).';
$string['webhook_received'] = 'Webhook ricevuto';
$string['webhook_service_disabled'] = 'Il servizio è disabilitato.';
$string['webhook_success'] = 'Webhook elaborato con successo.';
