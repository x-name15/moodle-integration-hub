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

namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * SOAP Transport Driver.
 *
 * Handles making SOAP requests using PHP's native SoapClient.
 * Expects 'base_url' to be the WSDL URL.
 * Expects 'endpoint' to be the SOAP Action / Method name.
 *
 * @package    local_integrationhub
 * @copyright  2026 Integration Hub Contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class soap implements contract
{
    use transport_utils;

    /**
     * @inheritDoc
     */
    public function execute(\stdClass $service, string $endpoint, array $payload, string $method = ''): array
    {
        $starttime = microtime(true);
        $attempts = 1;

        try {
            // Endpoint here acts as the SOAP function name (e.g., 'Add', 'Subtract').
            // Remove leading slash if present.
            $soapaction = ltrim($endpoint, '/');

            $options = [
                'connection_timeout' => (int)$service->timeout,
                'exceptions' => true,
                'trace' => true, // Enable tracing to get last request/response.
                'cache_wsdl' => WSDL_CACHE_DISK,
            ];

            // Handle Auth (Basic or specific Headers could be added here if needed).
            if ($service->auth_type === 'basic') {
            // Not standard in basic auth fields but common pattern. Assuming auth_token is user:pass.
            // Alternatively, native soap options for login and password could be added here.
            }

            $client = new \SoapClient($service->base_url, $options);

            // Execute SOAP call.
            // Payload is passed as arguments. Ideally payload should be an array mapping arguments.
            $response = $client->__soapCall($soapaction, [$payload]);

            // Convert response to JSON string for consistency with other transports.
            $responsejson = json_encode($response);

            return $this->success_result($responsejson, $starttime, $attempts, 200);
        }
        catch (\SoapFault $e) {
            return $this->error_result('SOAP Fault: ' . $e->getMessage(), $starttime, $attempts, 500);
        }
        catch (\Exception $e) {
            return $this->error_result('SOAP Error: ' . $e->getMessage(), $starttime, $attempts, 500);
        }
    }
}