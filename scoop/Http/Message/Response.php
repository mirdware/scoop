<?php

namespace Scoop\Http\Message;

class Response extends \Scoop\Http\Message
{
    private $statusCode;
    private $reasonPhrase;
    private static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    public function __construct($headers = array(), Stream $body = null)
    {
        parent::__construct(
            $headers,
            $body === null ? new Stream(fopen('php://temp', 'r+')) : $body
        );
        $this->statusCode = $body === null ? 204 : 200;
        $this->reasonPhrase = self::$statusTexts[$this->statusCode];
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException('Invalid status code', 791);
        }
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = empty($reasonPhrase) && isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
