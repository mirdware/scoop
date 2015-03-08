<?php
namespace Scoop\Http;

class NotFoundException extends Exception
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, array('HTTP/1.0 404 Not Found'), $code);
    }
}