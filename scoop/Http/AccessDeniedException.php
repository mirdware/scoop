<?php
namespace Scoop\Http;

class AccessDeniedException extends Exception
{
    public function __construct($message = 'Forbidden', \Exception $previous = null)
    {
        parent::__construct($message, 403, $previous, array('HTTP/1.0 403 Forbidden'));
    }
}
