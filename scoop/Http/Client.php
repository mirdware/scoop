<?php

namespace Scoop\Http;

class Client
{
    private $options;

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    public function sendRequest(\Scoop\Http\Message\Request $request)
    {
        $ch = curl_init();
        $responseBodyHandle = fopen('php://temp', 'r+');
        $responseHeaders = array();
        $options = $this->getOptions($request, $responseBodyHandle, $responseHeaders);
        curl_setopt_array($ch, array_replace($this->options, $options));
        if (curl_exec($ch) === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            fclose($responseBodyHandle);
            if (in_array($errno, array(
                CURLE_COULDNT_RESOLVE_PROXY,
                CURLE_COULDNT_RESOLVE_HOST,
                CURLE_COULDNT_CONNECT,
                CURLE_OPERATION_TIMEOUTED,
                CURLE_SSL_CONNECT_ERROR,
                CURLE_GOT_NOTHING,
                CURLE_SEND_ERROR,
                CURLE_RECV_ERROR
            ))) {
                throw new \Scoop\Http\Exception\Network($error, $request, $errno);
            }
            throw new \Scoop\Http\Exception\Request($error, $request, $errno);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        rewind($responseBodyHandle);
        $stream = new \Scoop\Http\Message\Stream($responseBodyHandle);
        return new \Scoop\Http\Message\Response($statusCode, $responseHeaders, $stream);
    }

    private function getOptions(\Scoop\Http\Message\Request $request, $responseBodyHandle, &$responseHeaders)
    {
        $method = $request->getMethod();
        $options = array(
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FILE => $responseBodyHandle,
            CURLOPT_HEADERFUNCTION => function($ch, $headerLine) use (&$responseHeaders) {
                if (strpos($headerLine, ':') !== false) {
                    list($key, $value) = explode(':', $headerLine, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (!isset($responseHeaders[$key])) {
                        $responseHeaders[$key] = array();
                    }
                    $responseHeaders[$key][] = $value;
                }
                return strlen($headerLine);
            }
        );
        $requestHeaders = array();
        foreach ($request->getHeaders() as $name => $values) {
            $requestHeaders[] = $name . ': ' . implode(', ', $values);
        }
        $options[CURLOPT_HTTPHEADER] = $requestHeaders;
        if ($method === 'get' || $method === 'head') {
            return $options;
        }
        if ($method === 'post') {
            $options[CURLOPT_POST] = true;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }
        $body = $request->getBody();
        $size = $body->getSize();
        if ($size !== 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            if ($size !== null && $size < 2097152) {
                $options[CURLOPT_POSTFIELDS] = $body->getContents();
            } else {
                $options[CURLOPT_UPLOAD] = true;
                $options[CURLOPT_INFILESIZE] = $size;
                $options[CURLOPT_READFUNCTION] = function($ch, $fd, $length) use ($body) {
                    return $body->read($length);
                };
            }
        }
        return $options;
    }
}
