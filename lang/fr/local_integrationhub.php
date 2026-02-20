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

// General.
$string['pluginname'] = 'Integration Hub';
$string['dashboard'] = 'Tableau de bord';
$string['dashboard_desc'] = 'Gérez vos intégrations de services externes depuis le tableau de bord Integration Hub.';
$string['gotodashboard'] = 'Aller au Tableau de bord';

// Capabilities.
$string['integrationhub:manage'] = 'Gérer les services Integration Hub';
$string['integrationhub:view'] = 'Voir le tableau de bord Integration Hub';

// Settings.
$string['default_timeout'] = 'Délai d\'attente par défaut (secondes)';
$string['default_timeout_desc'] = 'Délai d\'attente par défaut des requêtes HTTP pour les nouveaux services.';
$string['default_max_retries'] = 'Retries maximum par défaut';
$string['default_max_retries_desc'] = 'Nombre maximum de retries (tentatives) par défaut pour les nouveaux services.';
$string['max_log_entries'] = 'Entrées de journal maximum';
$string['max_log_entries_desc'] = 'Nombre maximum d\'entrées de journal à conserver dans la base de données. Les entrées plus anciennes sont supprimées automatiquement lorsque cette limite est dépassée. Mettez 0 pour illimité (non recommandé).';

// Dashboard.
$string['services'] = 'Services enregistrés';
$string['noservices'] = 'Aucun service enregistré pour le moment. Cliquez sur "Ajouter un service" pour commencer.';
$string['addservice'] = 'Ajouter un service';
$string['editservice'] = 'Modifier le service';
$string['deleteservice'] = 'Supprimer le service';
$string['deleteconfirm'] = 'Voulez-vous vraiment supprimer le service "{$a}" ? Cela supprimera également tous les journaux associés et l\'état du Circuit Breaker.';
$string['viewlogs'] = 'Voir les journaux';
$string['refreshstatus'] = 'Actualiser le statut';

// Service form.
$string['servicename'] = 'Nom d\'identification / Slug';
$string['servicename_help'] = 'Un identifiant unique pour ce service. Ce nom sera utilisé dans les règles et le code. Les espaces sont autorisés, mais l\'alphanumérique est recommandé.';
$string['resetallcircuits'] = 'Réinitialiser tous les circuits';
$string['allcircuitsreset'] = 'Tous les circuits de service ont été réinitialisés.';
// Duplicate baseurl in EN omitted.
$string['baseurl'] = 'URL de base / Connexion';
$string['base_url_help'] = 'URL de base du service externe.';
$string['url_help_rest'] = 'Exemple : https://api.service.com/v1';
$string['url_help_amqp'] = 'Exemple : amqp://user:pass@host:5672/vhost';
$string['authtype'] = 'Type d\'authentification : ';
$string['authtype_bearer'] = 'Jeton Bearer';
$string['authtype_apikey'] = 'Clé API';
$string['authtoken'] = 'Jeton / Identifiant';
$string['authtoken_help'] = 'Le jeton d\'authentification ou clé API pour ce service.';
$string['timeout'] = 'Délai d\'attente (secondes)';
$string['maxretries'] = 'Retries maximum';
$string['retrybackoff'] = 'Retry backoff (secondes)';
$string['retrybackoff_help'] = 'Délai initial de backoff pour les retries. Double à chaque tentative (backoff exponentiel).';
$string['cbfailurethreshold'] = 'Seuil du Circuit Breaker';
$string['cbfailurethreshold_help'] = 'Nombre d\'échecs consécutifs avant l\'ouverture du Circuit Breaker.';
$string['cbcooldown'] = 'Refroidissement du Circuit Breaker (secondes)';
$string['cbcooldown_help'] = 'Secondes à attendre avant de tester à nouveau le service (état half-open).';
$string['enabled'] = 'Activé';

// Service form actions.
$string['saveservice'] = 'Enregistrer le service';
$string['cancel'] = 'Annuler';
$string['servicecreated'] = 'Service "{$a}" créé avec succès.';
$string['serviceupdated'] = 'Service "{$a}" mis à jour avec succès.';
$string['servicedeleted'] = 'Service "{$a}" supprimé avec succès.';

// Table headers.
$string['col_name'] = 'Nom';
$string['col_baseurl'] = 'URL de base';
$string['col_authtype'] = 'Auth';
$string['col_circuit'] = 'Circuit';
$string['col_latency'] = 'Latence moyenne';
$string['col_errors'] = 'Erreurs récentes';
$string['col_actions'] = 'Actions';
$string['col_enabled'] = 'Statut';
$string['status_active'] = 'Actif';
$string['status_disabled'] = 'Désactivé';

// Circuit states.
$string['circuit_closed'] = 'CLOSED';
$string['circuit_open'] = 'OPEN';
$string['circuit_halfopen'] = 'HALF-OPEN';
$string['resetcircuit'] = 'Réinitialiser le circuit';
$string['circuitreset'] = 'Le Circuit Breaker pour "{$a}" a été réinitialisé.';

// Errors.
$string['service_not_found'] = 'Le service "{$a}" n\'est pas enregistré dans Integration Hub.';
$string['service_disabled'] = 'Le service "{$a}" est actuellement désactivé.';
$string['error_name_exists'] = 'Un service avec ce nom existe déjà.';
$string['error_invalid_url'] = 'Veuillez saisir une URL valide.';
$string['error_name_required'] = 'Le nom du service est requis.';
$string['error_url_required'] = 'L\'URL principale est requise.';

// Logs.
$string['logs'] = 'Journaux de requêtes';
$string['nologs'] = 'Aucune requête journalisée pour le moment.';
$string['col_endpoint'] = 'Endpoint';
$string['col_method'] = 'Méthode';
$string['col_status'] = 'Statut';
$string['col_latency_ms'] = 'Latence (ms)';
$string['col_attempts'] = 'Tentatives';
$string['col_success'] = 'Résultat';
$string['col_error'] = 'Erreur';
$string['col_time'] = 'Heure';
$string['result_success'] = 'OK';
$string['result_failure'] = 'ÉCHEC';
$string['clearlogs'] = 'Effacer les journaux';
$string['clearlogs_confirm'] = 'Voulez-vous vraiment supprimer TOUS les journaux ? Cette action est irréversible.';
$string['logs_cleared'] = 'Tous les journaux ont été effacés.';
$string['logs_cleared_service'] = 'Les journaux de ce service ont été effacés.';

// Privacy.
$string['privacy:metadata'] = 'Le plugin Integration Hub ne stocke pas de données personnelles d\'utilisateur. Il enregistre uniquement les requêtes HTTP vers des services externes.';

// Event Rules.
$string['rules'] = 'Règles d\'intégration';
$string['addrule'] = 'Ajouter une règle';
$string['editrule'] = 'Modifier la règle';
$string['rule_event'] = 'Événement Moodle';
$string['rule_event_help'] = 'Saisissez le nom complet de la classe de l\'événement (ex: \core\event\user_created). Vous pouvez le sélectionner dans la liste ou en taper un personnalisé. Remarque : Les événements personnalisés doivent également être enregistrés dans db/events.php pour être interceptés.';
$string['rule_service'] = 'Service cible';
$string['rule_endpoint'] = 'Endpoint Override';
$string['rule_method'] = 'Méthode HTTP';
$string['rule_template'] = 'Payload Template (JSON)';
$string['rule_template_help'] = 'Utilisez des espaces réservés comme {{userid}}, {{courseid}}, {{objectid}}. Laissez vide pour envoyer les données brutes de l\'événement.';
$string['selectevent'] = 'Sélectionner un événement...';
$string['selectservice'] = 'Sélectionnez un service...';
$string['rulecreated'] = 'Règle créée avec succès.';
$string['ruleupdated'] = 'Règle mise à jour avec succès.';
$string['ruledeleted'] = 'Règle supprimée.';
$string['deleteconfirmrule'] = 'Voulez-vous vraiment supprimer cette règle ?';
$string['col_type'] = 'Type';
$string['col_event'] = 'Événement';
$string['col_service'] = 'Service';
$string['col_endpoint'] = 'Endpoint';
$string['norules'] = 'Aucune règle d\'intégration définie pour le moment.';
$string['servicetype'] = 'Type de service';
$string['type_rest'] = 'REST API';
$string['type_amqp'] = 'AMQP (RabbitMQ)';
$string['type_soap'] = 'SOAP (Legacy)';
$string['amqp_builder'] = 'AMQP Connection Builder';
$string['amqp_host'] = 'Host';
$string['amqp_port'] = 'Port';
$string['amqp_user'] = 'Utilisateur';
$string['amqp_pass'] = 'Mot de passe';
$string['amqp_vhost'] = 'vHost';
$string['amqp_exchange'] = 'Exchange';
$string['amqp_routing_key_default'] = 'Routing Key';
$string['amqp_queue_declare'] = 'Queue à déclarer (Optionnel)';
$string['amqp_routing_key_help'] = 'La Routing Key par défaut utilisée lors de la publication de messages. Les événements peuvent remplacer cela via le champ "Endpoint".';
$string['amqp_queue_help'] = 'Si défini, cette Queue sera déclarée (créée) avant la publication. Utile pour les modèles de "Work Queue".';
$string['amqp_dlq'] = 'Dead Letter Queue (Optionnel)';

// Queue.
$string['queue'] = 'Moniteur de Queue';
$string['queue_desc'] = 'Surveiller les événements en attente et en échec.';
$string['no_pending_tasks'] = 'Aucune tâche en attente dans la queue.';
$string['col_failures'] = 'Échecs';
$string['col_next_run'] = 'Prochaine exécution';
$string['col_created'] = 'Créé';
$string['retry_now'] = 'Réessayer maintenant';
$string['failed'] = 'Échoué';
$string['retry'] = 'Retry';
$string['pending'] = 'En attente';
$string['task_retried'] = 'Tâche priorisée pour une exécution immédiate.';
$string['task_retry_failed'] = 'Échec de la nouvelle tentative.';
$string['dlq'] = 'Dead Letter Queue (DLQ)';
$string['dlq_desc'] = 'Événements qui ont échoué définitivement et nécessitent une intervention manuelle.';
$string['replay'] = 'Rejouer (Replay)';
$string['delete_dlq'] = 'Supprimer';
$string['no_dlq_items'] = 'Aucun événement dans la dead letter queue.';
$string['dlq_replayed'] = 'Événement remis dans la queue avec succès.';
$string['dlq_deleted'] = 'Événement supprimé de la dead letter queue.';
$string['task_deleted'] = 'Tâche supprimée avec succès.';
$string['task_delete_failed'] = 'Échec de la suppression de la tâche.';
$string['orphans_purged'] = '{$a} tâches orphelines purgées avec succès.';
$string['purge_orphans'] = 'Purger les orphelins';
$string['purge_orphans_confirm'] = 'Voulez-vous vraiment supprimer toutes les tâches dont les règles ont été supprimées ?';
$string['task_delete_confirm'] = 'Voulez-vous vraiment supprimer cette tâche ?';
$string['dlq_delete_confirm'] = 'Voulez-vous vraiment supprimer cet élément de la DLQ ?';

// Queue Payload Viewer
$string['view_payload'] = 'Voir le Payload';
$string['payload_source'] = 'Données source';
$string['payload_final'] = 'Payload Final';
$string['close'] = 'Fermer';

// Dashboard Charts.
$string['integrationstatus'] = 'Statut d\'intégration (Historique)';
$string['latencytrend'] = 'Tendance de latence (200 dernières r.)';
$string['success'] = 'Succès';
$string['failure'] = 'Échec';
$string['avglatency'] = 'Latence moyenne (ms)';

// Webhook & Bidirectional.
$string['webhook_received'] = 'Webhook reçu';
$string['webhook_invalid_token'] = 'Jeton d\'authentification invalide.';
$string['webhook_invalid_service'] = 'Service introuvable.';
$string['webhook_success'] = 'Webhook traité avec succès.';
$string['webhook_error'] = 'Erreur lors du traitement du Webhook.';
$string['direction'] = 'Direction';
$string['direction_outbound'] = 'Sortant';
$string['direction_inbound'] = 'Entrant';
$string['response_queue'] = 'Response Queue';
$string['response_queue_help'] = 'Nom de la queue AMQP pour consommer les messages de réponse. Laissez vide pour désactiver le composant AMQP entrant.';
$string['task_consume_responses'] = 'Consommer les messages de réponse AMQP';
$string['col_direction'] = 'Direction';

// Sent Events Page.
$string['sent_events'] = 'Événements envoyés';
$string['latest_events_title'] = 'Les {$a} derniers événements envoyés';
$string['latest_events_limit'] = 'Limite des événements envoyés';
$string['latest_events_limit_desc'] = 'Nombre de derniers événements envoyés à afficher dans l\'onglet "Événements envoyés".';
$string['no_events_logged'] = 'Aucun événement envoyé récemment.';
