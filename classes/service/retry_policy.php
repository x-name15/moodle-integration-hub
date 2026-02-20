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

defined('MOODLE_INTERNAL') || die();

/**
 * Retry Policy — configurable retry logic with exponential backoff.
 *
 * @package    local_integrationhub
 * @copyright  Mr Jacket - Felix Manrique
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class retry_policy
{
    /** @var int Maximum number of attempts (1 = no retry). */
    private $maxretries;

    /** @var int Initial backoff in seconds. */
    private $backoffseconds;

    /**
     * Constructor.
     *
     * @param int $maxretries Maximum retry attempts.
     * @param int $backoffseconds Initial backoff delay in seconds (doubles each retry).
     */
    public function __construct(int $maxretries = 3, int $backoffseconds = 1)
    {
        $this->maxretries = max(0, $maxretries);
        $this->backoffseconds = max(1, $backoffseconds);
    }

    /**
     * Create a retry policy from a service record.
     *
     * @param \stdClass $service A service record from the database.
     * @return self
     */
    public static function from_service(\stdClass $service): self
    {
        return new self(
            (int)($service->max_retries ?? 3),
            (int)($service->retry_backoff ?? 1)
            );
    }

    /**
     * Execute a callable with retry logic.
     *
     * The callable should throw an exception on failure.
     * On success, it should return the result.
     *
     * @param callable $operation The operation to execute. Receives the attempt number (0-indexed).
     * @return mixed The result of the successful operation.
     * @throws \Exception The last exception if all attempts fail.
     */
    public function execute(callable $operation)
    {
        $lastexception = null;
        $attempts = $this->maxretries + 1; // Plus 1 for the initial attempt.

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                return $operation($attempt);
            }
            catch (\Exception $e) {
                $lastexception = $e;

                // Don't sleep after the last attempt.
                if ($attempt < $attempts - 1) {
                    $delay = $this->backoffseconds * pow(2, $attempt);
                    // Cap delay at 60 seconds.
                    $delay = min($delay, 60);
                    sleep($delay);
                }
            }
        }

        throw $lastexception;
    }

    /**
     * Get the total number of attempts (initial + retries).
     *
     * @return int
     */
    public function get_total_attempts(): int
    {
        return $this->maxretries + 1;
    }
}