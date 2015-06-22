<?php
namespace Scoop\Http;

class MethodNotAllowedException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(405, $message, $previous, array('HTTP/1.0 405 Method Not Allowed'), $code);
    }
}
