<?php

namespace Scoop\Http\Exception;

class NotFound extends \UnexpectedValueException
{
    public function __construct($message = 'not found', $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
