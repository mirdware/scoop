<?php

namespace Scoop\Http;

/**
 * @deprecated 7.1
 */
class ConflictException extends Exception
{
    public function __construct($message = 'Bad Indexed', \Exception $previous = null)
    {
        parent::__construct($message, 409, $previous, array('HTTP/1.0 409 Conflict'));
    }
}
