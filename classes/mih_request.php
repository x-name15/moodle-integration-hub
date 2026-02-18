<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_integrationhub;

defined('MOODLE_INTERNAL') || die();

/**
 * Fluent Request Builder for Integration Hub.
 *
 * Helper class returned by mih::send() to construct requests fluently.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mih_request
{

    /** @var string Service slug. */
    private $service;

    /** @var string Endpoint path. */
    private $path = '/';

    /** @var array Request payload. */
    private $data = [];

    /** @var string HTTP method. */
    private $method = 'POST';

    /**
     * Constructor.
     *
     * @param string $service Service slug.
     */
    public function __construct(string $service)
    {
        $this->service = $service;
    }

    /**
     * Set the endpoint path.
     *
     * @param string $path E.g. '/api/users'
     * @return self
     */
    public function to(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the payload data.
     *
     * @param array $data Data array.
     * @return self
     */
    public function with(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set the HTTP method (optional, defaults to POST).
     *
     * @param string $method E.g. 'GET', 'PUT'.
     * @return self
     */
    public function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Dispatch the request via the Gateway.
     *
     * @return mih_response
     * @throws \moodle_exception
     */
    public function dispatch(): mih_response
    {
        // Use the internal method of the MIH singleton.
        return mih::instance()->execute_request(
            $this->service,
            $this->path,
            $this->data,
            $this->method
        );
    }
}