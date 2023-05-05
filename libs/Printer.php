<?php

/**
 * Class Printer
 */
class Printer
{
    /**
     * Print request headers and response.
     *
     * @param Response $response
     */
    public static function printResponse(Response $response)
    {
        try {
            echo "Response headers: ";
            self::prettyPrintOutput($response->getHeaders());
            echo "Response payload: ";
            self::prettyPrintOutput($response->getBody());
        } catch (Exception $e) {
            self::prettyPrintOutput($e);
        }
    }

    /**
     * Pretty print ouput
     *
     * @param mixed $output
     */
    public static function prettyPrintOutput($output)
    {
        echo "\n";
        print_r($output);
        echo "\n";
    }

}
