<?php
namespace Scoop\Http;

class MethodNotAllowedException extends Exception
{
    public function __construct($message = 'Method Not Allowed', \Exception $previous = null)
    {
        parent::__construct($message, 405, $previous, array('HTTP/1.0 405 Method Not Allowed'));
    }
}
