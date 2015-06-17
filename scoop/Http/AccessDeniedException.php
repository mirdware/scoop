<?php
namespace Scoop\Http;

class AccessDeniedException extends Exception
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(403, $message, $previous, array('HTTP/1.0 403 Forbidden'), $code);
    }
}
