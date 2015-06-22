<?php
namespace Scoop\Http;

class AccessDeniedException extends Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(403, $message, $previous, array('HTTP/1.0 403 Forbidden'), $code);
    }
}
