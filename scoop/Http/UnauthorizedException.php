<?php

namespace Scoop\Http;

/**
 * @deprecated 7.1
 */
class UnauthorizedException extends Exception
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        parent::__construct($message, 401, $previous, array('HTTP/1.0 401 Unauthorized'));
    }
}
