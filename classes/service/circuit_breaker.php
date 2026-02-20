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

namespace local_integrationhub\service;

/**
 * Circuit Breaker — prevents cascading failures by short-circuiting requests
 * to services that are consistently failing.
 *
 * States:
 *   CLOSED   → Normal operation. Requests pass through.
 *   OPEN     → Service is down. Requests fail immediately without calling the service.
 *   HALFOPEN → Testing. One request is allowed through to check if the service recovered.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket - Felix Manrique
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class circuit_breaker
{
    /** @var string Circuit state: closed (normal). */
    const STATE_CLOSED = 'closed';
    /** @var string Circuit state: open (blocking). */
    const STATE_OPEN = 'open';
    /** @var string Circuit state: half-open (testing). */
    const STATE_HALFOPEN = 'halfopen';

    /** @var string DB table for circuit state. */
    const TABLE = 'local_integrationhub_cb';

    /** @var int The service ID. */
    private $serviceid;

    /** @var int Failure count threshold before opening. */
    private $failurethreshold;

    /** @var int Cooldown seconds before transitioning to half-open. */
    private $cooldown;

    /**
     * Constructor.
     *
     * @param int $serviceid The service ID.
     * @param int $failurethreshold Failures before circuit opens.
     * @param int $cooldown Seconds to wait before half-open.
     */
    public function __construct(int $serviceid, int $failurethreshold = 5, int $cooldown = 30) {
        $this->serviceid = $serviceid;
        $this->failurethreshold = $failurethreshold;
        $this->cooldown = $cooldown;
    }

    /**
     * Create a circuit breaker from a service record.
     *
     * @param \stdClass $service A service record from the database.
     * @return self
     */
    public static function from_service(\stdClass $service): self {
        return new self(
            (int)$service->id,
            (int)($service->cb_failure_threshold ?? 5),
            (int)($service->cb_cooldown ?? 30)
        );
    }

    /**
     * Check if a request is allowed to proceed.
     *
     * Handles automatic state transitions:
     *   OPEN + cooldown expired → HALFOPEN (allow one test request)
     *
     * @return bool True if the request should proceed, false if circuit is open.
     */
    public function is_available(): bool {
        $state = $this->get_state();

        if ($state->state === self::STATE_CLOSED) {
            return true;
        }

        if ($state->state === self::STATE_OPEN) {
            if ($state->last_failure && (time() - $state->last_failure) >= $this->cooldown) {
                $this->set_state(self::STATE_HALFOPEN);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Record a successful request. Resets the circuit to CLOSED.
     */
    public function record_success(): void {
        global $DB;

        $state = $this->get_state();
        $state->state = self::STATE_CLOSED;
        $state->failure_count = 0;
        $state->timemodified = time();

        $DB->update_record(self::TABLE, $state);
    }

    /**
     * Record a failed request. Increments failure count and opens
     * the circuit if the threshold is reached.
     */
    public function record_failure(): void {
        global $DB;

        $state = $this->get_state();
        $state->failure_count++;
        $state->last_failure = time();
        $state->timemodified = time();

        if ($state->failure_count >= $this->failurethreshold || $state->state === self::STATE_HALFOPEN) {
            $state->state = self::STATE_OPEN;
        }

        $DB->update_record(self::TABLE, $state);
    }

    /**
     * Get the current circuit state from the database.
     *
     * @return \stdClass The circuit record.
     */
    public function get_state(): \stdClass {
        global $DB;

        $state = $DB->get_record(self::TABLE, ['serviceid' => $this->serviceid]);

        if (!$state) {
            $state = new \stdClass();
            $state->serviceid = $this->serviceid;
            $state->state = self::STATE_CLOSED;
            $state->failure_count = 0;
            $state->last_failure = null;
            $state->timemodified = time();
            $state->id = $DB->insert_record(self::TABLE, $state);
        }

        return $state;
    }

    /**
     * Get the current state as a human-readable string.
     *
     * @return string
     */
    public function get_state_label(): string {
        return strtoupper($this->get_state()->state);
    }

    /**
     * Manually reset the circuit to CLOSED state.
     */
    public function reset(): void {
        global $DB;

        $state = $this->get_state();
        $state->state = self::STATE_CLOSED;
        $state->failure_count = 0;
        $state->last_failure = null;
        $state->timemodified = time();

        $DB->update_record(self::TABLE, $state);
    }

    /**
     * Set the circuit state.
     *
     * @param string $newstate One of STATE_CLOSED, STATE_OPEN, STATE_HALFOPEN.
     */
    private function set_state(string $newstate): void {
        global $DB;

        $state = $this->get_state();
        $state->state = $newstate;
        $state->timemodified = time();

        $DB->update_record(self::TABLE, $state);
    }
}
