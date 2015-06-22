<?php
namespace Scoop\Http;

class BadRequestException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(400, $message, $previous, array('HTTP/1.0 400 Bad Request'), $code);
    }
}
