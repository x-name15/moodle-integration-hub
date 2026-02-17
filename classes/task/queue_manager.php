<?php
namespace local_integrationhub\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to manage the Integration Hub task queue.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_manager {

    /**
     * Get all pending dispatch_event_tasks.
     *
     * @return array List of task objects with expanded custom data.
     */
    public static function get_pending_tasks() {
        global $DB;

        // Fetch adhoc tasks for our specific class.
        $tasks = $DB->get_records('task_adhoc', ['classname' => '\local_integrationhub\task\dispatch_event_task'], 'nextruntime ASC');

        $results = [];
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            
            // Enrich with rule/service info if available.
            $rule = $DB->get_record('local_integrationhub_rules', ['id' => $data->ruleid ?? 0]);
            $service = $rule ? $DB->get_record('local_integrationhub_svc', ['id' => $rule->serviceid]) : null;

            $task->eventname = $rule->eventname ?? 'Unknown (Rule deleted)';
            $task->servicename = $service->name ?? 'Unknown';
            $task->ruleid = $data->ruleid ?? 0;
            
            $results[] = $task;
        }

        return $results;
    }

    /**
     * Force a retry of a failed/pending task by resetting its faildelay and nextruntime.
     * 
     * @param int $taskid The adhoc task ID.
     * @return bool True on success.
     */
    public static function retry_task($taskid) {
        global $DB;

        $task = $DB->get_record('task_adhoc', ['id' => $taskid, 'classname' => '\local_integrationhub\task\dispatch_event_task']);

        if (!$task) {
            return false;
        }

        $update = new \stdClass();
        $update->id = $taskid;
        $update->faildelay = 0;
        $update->nextruntime = time() - 1; // Run immediately.
        
        return $DB->update_record('task_adhoc', $update);
    }

    /**
     * Delete a single adhoc task by ID.
     *
     * @param int $taskid The adhoc task ID.
     * @return bool True on success.
     */
    public static function delete_task(int $taskid): bool {
        global $DB;
        return $DB->delete_records('task_adhoc', [
            'id' => $taskid,
            'classname' => '\local_integrationhub\task\dispatch_event_task',
        ]);
    }

    /**
     * Purge all orphan tasks (tasks whose rule no longer exists).
     *
     * @return int Number of tasks purged.
     */
    public static function purge_orphan_tasks(): int {
        global $DB;

        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\local_integrationhub\task\dispatch_event_task',
        ]);

        $count = 0;
        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);
            $ruleid = $data->ruleid ?? 0;

            $purge = false;
            if ($ruleid <= 0) {
                $purge = true;
            } else {
                $rule = $DB->get_record('local_integrationhub_rules', ['id' => $ruleid]);
                if (!$rule) {
                    $purge = true;
                } else if (!$DB->record_exists('local_integrationhub_svc', ['id' => $rule->serviceid])) {
                    // Rule exists but service is gone.
                    $purge = true;
                }
            }

            if ($purge) {
                $DB->delete_records('task_adhoc', ['id' => $task->id]);
                $count++;
            }
        }

        return $count;
    }
}
