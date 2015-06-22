<?php
namespace Scoop\Http;

class GoneException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(410, $message, $previous, array('HTTP/1.0 410 Gone'), $code);
    }
}
