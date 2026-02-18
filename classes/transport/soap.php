<?php
namespace local_integrationhub\transport;

defined('MOODLE_INTERNAL') || die();

/**
 * SOAP Transport Driver.
 *
 * Handles making SOAP requests using PHP's native SoapClient.
 * Expects 'base_url' to be the WSDL URL.
 * Expects 'endpoint' to be the SOAP Action / Method name.
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
            // endpoint here acts as the SOAP function name (e.g., 'Add', 'Subtract').
            // remove leading slash if present.
            $soap_action = ltrim($endpoint, '/');

            $options = [
                'connection_timeout' => (int)$service->timeout,
                'exceptions' => true,
                'trace' => true, // Enable tracing to get last request/response.
                'cache_wsdl' => WSDL_CACHE_DISK,
            ];

            // Handle Auth (Basic or specific Headers could be added here if needed).
            if ($service->auth_type === 'basic') {
            // Not standard in basic auth fields but common pattern. Assuming auth_token is user:pass? 
            // Alternatively, if fields existed:
            // $options['login'] = ...;
            // $options['password'] = ...;
            }

            $client = new \SoapClient($service->base_url, $options);

            // Execute SOAP call.
            // Payload is passed as arguments. Ideally payload should be an array mapping arguments.
            $response = $client->__soapCall($soap_action, [$payload]);

            // Convert response to JSON string for consistency with other transports.
            $response_json = json_encode($response);

            return $this->success_result($response_json, $starttime, $attempts, 200);

        }
        catch (\SoapFault $e) {
            return $this->error_result('SOAP Fault: ' . $e->getMessage(), $starttime, $attempts, 500);
        }
        catch (\Exception $e) {
            return $this->error_result('SOAP Error: ' . $e->getMessage(), $starttime, $attempts, 500);
        }
    }
}