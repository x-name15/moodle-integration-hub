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

namespace local_integrationhub;

/**
 * MIH Response — immutable value object wrapping the result of a MIH request.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mih_response
{
    /** @var bool Whether the request was successful. */
    public $success;

    /** @var int|null HTTP status code. */
    public $httpstatus;

    /** @var string|null Raw response body. */
    public $body;

    /** @var string|null Error message if failed. */
    public $error;

    /** @var int Latency in milliseconds. */
    public $latencyms;

    /** @var int Number of attempts made. */
    public $attempts;

    /**
     * Constructor.
     *
     * @param bool        $success    Whether the request succeeded.
     * @param int|null    $httpstatus HTTP status code.
     * @param string|null $body       Response body.
     * @param string|null $error      Error message.
     * @param int         $latencyms  Latency in ms.
     * @param int         $attempts   Number of attempts.
     */
    public function __construct(
        bool $success,
        ?int $httpstatus,
        ?string $body,
        ?string $error,
        int $latencyms,
        int $attempts
    ) {
        $this->success = $success;
        $this->httpstatus = $httpstatus;
        $this->body = $body;
        $this->error = $error;
        $this->latencyms = $latencyms;
        $this->attempts = $attempts;
    }

    /**
     * Decode the JSON response body.
     *
     * @param bool $assoc If true, return associative array. Default true.
     * @return mixed Decoded JSON data.
     * @throws \coding_exception If body is not valid JSON.
     */
    public function json(bool $assoc = true) {
        if ($this->body === null) {
            return null;
        }
        $data = json_decode($this->body, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \coding_exception('Invalid JSON response: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Check if this response is successful.
     *
     * @return bool
     */
    public function is_ok(): bool {
        return $this->success;
    }
}
