<?php

require_once('libs/http/Response.php');

/**
 * Class Client
 *
 * Usage:
 * - Send HTTP requests to the given URL using different methods, such as GET, POST, etc.
 * - Send JSON payloads
 * - Send custom HTTP headers
 * - Throw an exception for erroneous HTTP response codes (e.g. 4xx, 5xx)
 */
class Client
{
    public const METHOD_GET      = 'GET';
    public const METHOD_POST     = 'POST';
    public const METHOD_PUT      = 'PUT';
    public const METHOD_DELETE   = 'DELETE';
    public const METHOD_HEAD     = 'HEAD';
    public const METHOD_OPTIONS  = 'OPTIONS';

    /**
     * GET request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function get(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_GET, $url, $body, $headers);
    }

    /**
     * POST request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function post(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_POST, $url, $body, $headers);
    }

    /**
     * PUT request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function put(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_PUT, $url, $body, $headers);
    }

    /**
     * DELETE request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function delete(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_DELETE, $url, $body, $headers);
    }

    /**
     * HEAD request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function head(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_HEAD, $url, $body, $headers);
    }

    /**
     * OPTIONS request.
     *
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function options(string $url, array $body = null, array $headers = []): Response
    {
        return self::send(self::METHOD_OPTIONS, $url, $body, $headers);
    }

    /**
     * Build structure for HTTP Request.
     *
     * @param string $method Method (GET, POST, etc.)
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return array Request data: 0 - url; 1 - options
     */
    private static function buildRequest(string $method, string $url, array $body = null, array $headers = []): array
    {
        $content = '';

        $method  = strtoupper($method);
        $headers = array_change_key_case($headers, CASE_LOWER);

        switch ($method) {
            case self::METHOD_HEAD:
            case self::METHOD_OPTIONS:
            case self::METHOD_GET:
                if (is_array($body)) {
                    if (strpos($url, '?') !== false) {
                        $url .= '&';
                    } else {
                        $url .= '?';
                    }

                    $url .= urldecode(http_build_query($body));
                }
                break;
            case self::METHOD_DELETE:
            case self::METHOD_PUT:
            case self::METHOD_POST:
                if (is_array($body)) {
                    if (!empty($headers['content-type'])) {
                        switch (trim($headers['content-type'])) {
                            case 'application/x-www-form-urlencoded':
                                $body = http_build_query($body);
                                break;
                            case 'application/json':
                                $body = json_encode($body);
                                break;
                        }
                    } else {
                        $headers['content-type'] = 'application/x-www-form-urlencoded';
                        $body                    = http_build_query($body);
                    }
                } elseif (empty($headers['content-type'])) {
                    $headers['content-type'] = 'application/x-www-form-urlencoded';
                }

                $content = $body;
                break;
        }

        $options = [
            'http' => [
                'method' => $method,
            ],
        ];

        if ($headers) {
            $options['http']['header'] = implode(
                "\r\n",
                array_map(
                    function ($v, $k) {
                        return sprintf("%s: %s", $k, $v);
                    },
                    $headers,
                    array_keys($headers)
                )
            );
        }

        if ($content) {
            $options['http']['content'] = $content;
        }

        return [$url, $options];
    }

    /**
     * Sends HTTP request.
     *
     * @param string $method Method (GET, POST, etc.)
     * @param string $url Request URL
     * @param array|null $body Request body
     * @param array $headers Request headers
     * @return Response
     * @throws Exception
     */
    public static function send(string $method, string $url, array $body = null, array $headers = []): Response
    {
        [$url, $options] = self::buildRequest($method, $url, $body, $headers);

        $context = stream_context_create($options);
        $result  = file_get_contents($url, false, $context);

        if ($result === false) {
            $headers_line = implode(',', $http_response_header);
            // Retrieve status code
            preg_match('{HTTP\/\S*\s(\d{3})}', $headers_line, $match);
            $status = $match[1] ?? 400;
            $responseText = self::statusCodeToText($status);
            // If the status code in 4xx, throw a client error exception.
            if (strpos($status, '4') === 0) {
                throw new Exception("Unexpected client error: {$status}::{$responseText} while fetching {$url}\nHeaders: " . $headers_line);
            } // If the status code in 5xx, throw a server error exception.
            else if (strpos($status, '5') === 0) {
                throw new Exception("Unexpected server error: {$status}::{$responseText} while fetching {$url}\nHeaders: " . $headers_line);
            }
        }

        return new Response($result, $http_response_header);
    }

    /**
     * Convert status code to response text
     *
     * @param int $code
     * @return string
     */
    private static function statusCodeToText(int $code): string
    {
        switch ($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                $text = 'Unknown http status code "' . htmlentities($code) . '"';
                break;
        }
        return $text;
    }
}