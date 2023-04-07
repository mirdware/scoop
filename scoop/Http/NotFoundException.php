<?php

namespace Scoop\Http;

/**
 * @deprecated 7.1
 */
class NotFoundException extends Exception
{
    public function __construct($message = 'Not Found', \Exception $previous = null)
    {
        parent::__construct($message, 404, $previous, array('HTTP/1.0 404 Not Found'));
    }
}
