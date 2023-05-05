<?php

/**
 * Class Response
 *
 * Usage:
 * - Retrieve/parse HTTP response payloads
 * - Retrieve/parse HTTP response headers
 * - Throw an exception while processing HTTP response
 * - Return JSON payloads as associative arrays
 */
class Response
{
    /**
     * @var string
     */
    private string $response;

    /**
     * @var array|mixed
     */
    private $headers;

    /**
     * @param string $response
     * @param array $headers
     */
    public function __construct(string $response, array $headers = [])
    {
        $this->response = $response;
        $this->headers = $headers;
    }

    /**
     * Returns response body.
     *
     * @return array|string
     * @throws Exception
     */
    public function getBody()
    {
        $headerString = strtolower(implode(', ', $this->getHeaders()));
        // Return json body if specified in headers
        if (strpos($headerString, 'application/json') !== false) {
            $result = json_decode($this->response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            } else {
                throw new Exception("Error while decoding JSON body: " . json_last_error());
            }
        }

        return $this->response;
    }

    /**
     * Returns response headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}