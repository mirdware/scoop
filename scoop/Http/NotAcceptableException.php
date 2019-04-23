<?php
namespace Scoop\Http;

class NotAcceptableException extends Exception
{
    public function __construct($message = 'Not Acceptable', \Exception $previous = null)
    {
        parent::__construct($message, 406, $previous, array('HTTP/1.0 406 Not Acceptable'));
    }
}
