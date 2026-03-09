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
 * French language strings for Integration Hub.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrule'] = 'Ajouter une règle';
$string['addservice'] = 'Ajouter un service';
$string['allcircuitsreset'] = 'Tous les circuits de service ont été réinitialisés.';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_dlq'] = 'Dead Letter Queue (Optionnel)';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_host'] = 'Host';
$string['amqp_pass'] = 'Mot de passe';
$string['amqp_port'] = 'Port';
$string['amqp_queue_declare'] = 'Queue à déclarer (Optionnel)';
$string['amqp_queue_help'] = 'Si défini, cette Queue sera déclarée (créée) avant la publication. Utile pour les modèles de "Work Queue".';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_routing_key_help'] = 'La Routing Key par défaut utilisée lors de la publication de messages. Les événements peuvent remplacer cela via le champ "Endpoint".';
$string['amqp_user'] = 'Utilisateur';
$string['amqp_vhost'] = 'vHost';
$string['authtoken'] = 'Jeton / Identifiant';
$string['authtoken_help'] = 'Le jeton d\'authentification ou clé API pour ce service.';
$string['authtype'] = 'Type d\'authentification : ';
$string['authtype_apikey'] = 'Clé API';
$string['authtype_bearer'] = 'Jeton Bearer';
$string['avglatency'] = 'Latence moyenne (ms)';
$string['base_url_help'] = 'URL de base du service externe.';
$string['baseurl'] = 'URL de base / Connexion';
$string['cachedef_event_dedupe'] = 'Cache de déduplication des événements';
$string['cachedef_log_counter'] = 'Compteur d\'insertion de journaux pour purge efficace';
$string['cachedef_rate_limit'] = 'Cache de limitation de débit pour la protection des webhooks';
$string['cancel'] = 'Annuler';
$string['cbcooldown'] = 'Refroidissement du Circuit Breaker (secondes)';
$string['cbcooldown_help'] = 'Secondes à attendre avant de tester à nouveau le service (état half-open).';
$string['cbfailurethreshold'] = 'Seuil du Circuit Breaker';
$string['cbfailurethreshold_help'] = 'Nombre d\'échecs consécutifs avant l\'ouverture du Circuit Breaker.';
$string['circuit_closed'] = 'CLOSED';
$string['circuit_halfopen'] = 'HALF-OPEN';
$string['circuit_open'] = 'OPEN';
$string['circuitreset'] = 'Le Circuit Breaker pour "{$a}" a été réinitialisé.';
$string['clearlogs'] = 'Effacer les journaux';
$string['clearlogs_confirm'] = 'Voulez-vous vraiment supprimer TOUS les journaux ? Cette action est irréversible.';
$string['close'] = 'Fermer';
$string['col_actions'] = 'Actions';
$string['col_attempts'] = 'Tentatives';
$string['col_authtype'] = 'Auth';
$string['col_baseurl'] = 'URL de base';
$string['col_circuit'] = 'Circuit';
$string['col_created'] = 'Créé';
$string['col_direction'] = 'Direction';
$string['col_enabled'] = 'Statut';
$string['col_endpoint'] = 'Endpoint';
$string['col_error'] = 'Erreur';
$string['col_errors'] = 'Erreurs récentes';
$string['col_event'] = 'Événement';
$string['col_failures'] = 'Échecs';
$string['col_latency'] = 'Latence moyenne';
$string['col_latency_ms'] = 'Latence (ms)';
$string['col_method'] = 'Méthode';
$string['col_name'] = 'Nom';
$string['col_next_run'] = 'Prochaine exécution';
$string['col_service'] = 'Service';
$string['col_status'] = 'Statut';
$string['col_success'] = 'Résultat';
$string['col_time'] = 'Heure';
$string['col_type'] = 'Type';
$string['custom_headers'] = 'En-têtes personnalisés (JSON)';
$string['custom_headers_forbidden'] = 'Impossible de remplacer l\'en-tête critique : {$a}';
$string['custom_headers_help'] = 'En-têtes HTTP personnalisés optionnels sous forme d\'objet JSON. Exemple : {"X-API-Version": "v2", "X-Client-ID": "moodle"}. Note : Impossible de remplacer Authorization, Content-Type ou Accept.';
$string['custom_headers_invalid'] = 'Format JSON invalide pour les en-têtes personnalisés. Doit être un objet JSON valide.';
$string['dashboard'] = 'Tableau de bord';
$string['dashboard_desc'] = 'Gérez vos intégrations de services externes depuis le tableau de bord Integration Hub.';
$string['default_max_retries'] = 'Retries maximum par défaut';
$string['default_max_retries_desc'] = 'Nombre maximum de retries (tentatives) par défaut pour les nouveaux services.';
$string['default_timeout'] = 'Délai d\'attente par défaut (secondes)';
$string['default_timeout_desc'] = 'Délai d\'attente par défaut des requêtes HTTP pour les nouveaux services.';
$string['delete_dlq'] = 'Supprimer';
$string['deleteconfirm'] = 'Voulez-vous vraiment supprimer le service "{$a}" ? Cela supprimera également tous les journaux associés et l\'état du Circuit Breaker.';
$string['deleteconfirmrule'] = 'Voulez-vous vraiment supprimer cette règle ?';
$string['deleteservice'] = 'Supprimer le service';
$string['direction'] = 'Direction';
$string['direction_inbound'] = 'Entrant';
$string['direction_outbound'] = 'Sortant';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_delete_confirm'] = 'Voulez-vous vraiment supprimer cet élément de la DLQ ?';
$string['dlq_deleted'] = 'Événement supprimé de la dead letter queue.';
$string['dlq_desc'] = 'Événements qui ont échoué définitivement et nécessitent une intervention manuelle.';
$string['dlq_replayed'] = 'Événement remis dans la queue avec succès.';
$string['editrule'] = 'Modifier la règle';
$string['editservice'] = 'Modifier le service';
$string['enabled'] = 'Activé';
$string['error_invalid_url'] = 'Veuillez saisir une URL valide.';
$string['error_name_exists'] = 'Un service avec ce nom existe déjà.';
$string['error_name_required'] = 'Le nom du service est requis.';
$string['error_url_required'] = 'L\'URL principale est requise.';
$string['failed'] = 'Échoué';
$string['failure'] = 'Échec';
$string['gotodashboard'] = 'Aller au Tableau de bord';
$string['integrationhub:manage'] = 'Gérer les services Integration Hub';
$string['integrationhub:view'] = 'Voir le tableau de bord Integration Hub';
$string['integrationstatus'] = 'Statut d\'intégration (Historique)';
$string['latencytrend'] = 'Tendance de latence (200 dernières r.)';
$string['latest_events_limit'] = 'Limite des événements envoyés';
$string['latest_events_limit_desc'] = 'Nombre de derniers événements envoyés à afficher dans l\'onglet "Événements envoyés".';
$string['latest_events_title'] = 'Les {$a} derniers événements envoyés';
$string['log_purge_check_frequency'] = 'Fréquence de vérification de purge des journaux';
$string['log_purge_check_frequency_desc'] = 'Vérifier la purge des journaux toutes les N insertions. Des valeurs plus élevées réduisent la surcharge mais peuvent permettre aux journaux de croître temporairement au-delà de la limite. Recommandé : 50-100.';
$string['logs'] = 'Journaux de requêtes';
$string['logs_cleared'] = 'Tous les journaux ont été effacés.';
$string['logs_cleared_service'] = 'Les journaux de ce service ont été effacés.';
$string['max_log_entries'] = 'Entrées de journal maximum';
$string['max_log_entries_desc'] = 'Nombre maximum d\'entrées de journal à conserver dans la base de données. Les entrées plus anciennes sont supprimées automatiquement lorsque cette limite est dépassée. Mettez 0 pour illimité (non recommandé).';
$string['maxretries'] = 'Retries maximum';
$string['no_dlq_items'] = 'Aucun événement dans la dead letter queue.';
$string['no_events_logged'] = 'Aucun événement envoyé récemment.';
$string['no_pending_tasks'] = 'Aucune tâche en attente dans la queue.';
$string['nologs'] = 'Aucune requête journalisée pour le moment.';
$string['norules'] = 'Aucune règle d\'intégration définie pour le moment.';
$string['noservices'] = 'Aucun service enregistré pour le moment. Cliquez sur "Ajouter un service" pour commencer.';
$string['orphans_purged'] = '{$a} tâches orphelines purgées avec succès.';
$string['payload_final'] = 'Payload Final';
$string['payload_source'] = 'Données source';
$string['pending'] = 'En attente';
$string['pluginname'] = 'Integration Hub';
$string['privacy:metadata'] = 'Le plugin Integration Hub ne stocke pas de données personnelles d\'utilisateur. Il enregistre uniquement les requêtes HTTP vers des services externes.';
$string['privacy:metadata:external_services'] = 'Integration Hub transmet les données d\'événements Moodle à des services externes configurés par l\'administrateur du site. Les URL de destination exactes sont définies par l\'administrateur et peuvent varier. Les charges utiles des événements peuvent contenir des champs de données personnelles listés ci-dessous.';
$string['privacy:metadata:external_services:courseid'] = 'L\'identifiant du cours associé à l\'événement.';
$string['privacy:metadata:external_services:eventname'] = 'Le nom de l\'événement Moodle qui a déclenché l\'envoi.';
$string['privacy:metadata:external_services:objectid'] = 'L\'identifiant de l\'objet lié à l\'événement (par exemple, cours, devoir).';
$string['privacy:metadata:external_services:payload'] = 'La charge utile JSON envoyée au service externe, pouvant inclure tout contexte de données de l\'événement.';
$string['privacy:metadata:external_services:userid'] = 'L\'identifiant de l\'utilisateur associé à l\'événement.';
$string['purge_orphans'] = 'Purger les orphelins';
$string['purge_orphans_confirm'] = 'Voulez-vous vraiment supprimer toutes les tâches dont les règles ont été supprimées ?';
$string['queue'] = 'Moniteur de Queue';
$string['queue_desc'] = 'Surveiller les événements en attente et en échec.';
$string['ratelimit_heading'] = 'Limitation de débit';
$string['ratelimit_heading_desc'] = 'Configurer les limites de débit pour les requêtes webhook entrantes afin de prévenir les abus et les attaques DoS.';
$string['refreshstatus'] = 'Actualiser le statut';
$string['replay'] = 'Rejouer (Replay)';
$string['resetallcircuits'] = 'Réinitialiser tous les circuits';
$string['resetcircuit'] = 'Réinitialiser le circuit';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'Nom de la queue AMQP pour consommer les messages de réponse. Laissez vide pour désactiver le composant AMQP entrant.';
$string['result_failure'] = 'ÉCHEC';
$string['result_success'] = 'OK';
$string['retry'] = 'Retry';
$string['retry_now'] = 'Réessayer maintenant';
$string['retrybackoff'] = 'Retry backoff (secondes)';
$string['retrybackoff_help'] = 'Délai initial de backoff pour les retries. Double à chaque tentative (backoff exponentiel).';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_event'] = 'Événement Moodle';
$string['rule_event_help'] = 'Saisissez le nom complet de la classe de l\'événement (ex: \core\event\user_created). Vous pouvez le sélectionner dans la liste ou en taper un personnalisé. Remarque : Les événements personnalisés doivent également être enregistrés dans db/events.php pour être interceptés.';
$string['rule_method'] = 'Méthode HTTP';
$string['rule_service'] = 'Service cible';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Utilisez des espaces réservés comme {{userid}}, {{courseid}}, {{objectid}}. Laissez vide pour envoyer les données brutes de l\'événement.';
$string['rulecreated'] = 'Règle créée avec succès.';
$string['ruledeleted'] = 'Règle supprimée.';
$string['rules'] = 'Règles d\'intégration';
$string['ruleupdated'] = 'Règle mise à jour avec succès.';
$string['saveservice'] = 'Enregistrer le service';
$string['selectevent'] = 'Sélectionner un événement...';
$string['selectservice'] = 'Sélectionnez un service...';
$string['sent_events'] = 'Événements envoyés';
$string['service_disabled'] = 'Le service "{$a}" est actuellement désactivé.';
$string['service_not_found'] = 'Le service "{$a}" n\'est pas enregistré dans Integration Hub.';
$string['servicecreated'] = 'Service "{$a}" créé avec succès.';
$string['servicedeleted'] = 'Service "{$a}" supprimé avec succès.';
$string['servicename'] = 'Nom d\'identification / Slug';
$string['servicename_help'] = 'Un identifiant unique pour ce service. Ce nom sera utilisé dans les règles et le code. Les espaces sont autorisés, mais l\'alphanumérique est recommandé.';
$string['services'] = 'Services enregistrés';
$string['servicetype'] = 'Type de service';
$string['serviceupdated'] = 'Service "{$a}" mis à jour avec succès.';
$string['status_active'] = 'Actif';
$string['status_disabled'] = 'Désactivé';
$string['success'] = 'Succès';
$string['task_consume_responses'] = 'Consommer les messages de réponse AMQP';
$string['task_delete_confirm'] = 'Voulez-vous vraiment supprimer cette tâche ?';
$string['task_delete_failed'] = 'Échec de la suppression de la tâche.';
$string['task_deleted'] = 'Tâche supprimée avec succès.';
$string['task_purge_logs'] = 'Purger les anciens journaux d\'intégration';
$string['task_retried'] = 'Tâche priorisée pour une exécution immédiate.';
$string['task_retry_failed'] = 'Échec de la nouvelle tentative.';
$string['timeout'] = 'Délai d\'attente (secondes)';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_rest'] = 'REST API';
$string['type_soap'] = 'SOAP (Legacy)';
$string['url_help_amqp'] = 'Exemple : amqp://user:pass@host:5672/vhost';
$string['url_help_rest'] = 'Exemple : https://api.service.com/v1';
$string['view_payload'] = 'Voir le Payload';
$string['viewlogs'] = 'Voir les journaux';
$string['webhook_empty_body'] = 'Corps de la requête vide.';
$string['webhook_error'] = 'Erreur lors du traitement du Webhook.';
$string['webhook_invalid_json'] = 'JSON invalide : {$a}';
$string['webhook_invalid_service'] = 'Service introuvable.';
$string['webhook_invalid_token'] = 'Jeton d\'authentification invalide.';
$string['webhook_method_not_allowed'] = 'Méthode non autorisée. Utilisez POST.';
$string['webhook_missing_service'] = 'Paramètre requis manquant : service.';
$string['webhook_rate_limit'] = 'Limite de débit webhook (requêtes)';
$string['webhook_rate_limit_desc'] = 'Nombre maximum de requêtes autorisées par adresse IP et service dans la fenêtre de temps.';
$string['webhook_rate_limited'] = 'Limite de débit dépassée. Trop de requêtes.';
$string['webhook_rate_window'] = 'Fenêtre de débit webhook (secondes)';
$string['webhook_rate_window_desc'] = 'Fenêtre de temps en secondes pour la limitation de débit (ex. 60 pour des limites par minute).';
$string['webhook_received'] = 'Webhook reçu';
$string['webhook_service_disabled'] = 'Le service est désactivé.';
$string['webhook_success'] = 'Webhook traité avec succès.';
