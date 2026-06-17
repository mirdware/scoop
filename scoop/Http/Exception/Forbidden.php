<?php

namespace Scoop\Http\Exception;

class Forbidden extends \RuntimeException
{
    public function __construct($message = 'Access to this resource is forbidden', $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
