<?php
namespace Scoop\Http;

class GoneException extends Exception
{
    public function __construct($message = 'Gone', \Exception $previous = null)
    {
        parent::__construct($message, 410, $previous, array('HTTP/1.0 410 Gone'));
    }
}
