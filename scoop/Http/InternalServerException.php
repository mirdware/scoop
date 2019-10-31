<?php
namespace Scoop\Http;

class InternalServerException extends Exception
{
    public function __construct($message = 'Internal Server', \Exception $previous = null)
    {
        parent::__construct($message, 500, $previous, array($_SERVER["SERVER_PROTOCOL"].' 500 Internal Server Error'));
    }
}
