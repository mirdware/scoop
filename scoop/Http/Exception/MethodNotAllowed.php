<?php

namespace Scoop\Http\Exception;

class MethodNotAllowed extends \BadMethodCallException
{
    public function __construct($message = 'without specific method', $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
