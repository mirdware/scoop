<?php
namespace Scoop\Http;

class NotFoundException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(404, $message, $previous, array('HTTP/1.0 404 Not Found'), $code);
    }
}
