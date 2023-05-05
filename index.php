<?php

require_once 'libs/http/Client.php';
require_once 'libs/Printer.php';


/**
 * Get authentication token from CoreDNA
 * @throws Exception
 */
function fetchToken(): Response
{
    return Client::options('https://corednacom.corewebdna.com/assessment-endpoint.php');
}

/**
 * Submit data to CodeDNA.
 * @throws Exception
 */
function submitCoreDNAAssessment()
{
    // Fetch token using POST request
    $tokenResponse = fetchToken();

    // Submit candidate details
    $response = Client::post(
        'https://corednacom.corewebdna.com/assessment-endpoint.php',
        [
            'name' => 'Cong Tran',
            'email' => 'congtt@smartosc.com',
            'url' => 'https://github.com/CristTran/php7-http-client.git',
        ],
        [
            'Authorization' => 'Bearer ' . $tokenResponse->getBody(),
            'content-type' => 'application/json',
        ]
    );

    // Print response to console
    Printer::printResponse($response);
}


/**
 * Main function
 */
try {
    submitCoreDNAAssessment();
} catch (Exception $e) {
    // Print exception to console
    Printer::prettyPrintOutput($e);
}