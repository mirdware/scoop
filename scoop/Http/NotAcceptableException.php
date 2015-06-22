<?php
namespace Scoop\Http;

class NotAcceptableException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(406, $message, $previous, array('HTTP/1.0 406 Not Acceptable'), $code);
    }
}
