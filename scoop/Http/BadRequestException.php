<?php
namespace Scoop\Http;

class BadRequestException extends Exception
{
    public function __construct($message = 'Bad Formatted', \Exception $previous = null)
    {
        parent::__construct($message, 400, $previous, array('HTTP/1.0 400 Bad Request'));
    }
}
