<?php

namespace Scoop\Http\Exception;

class Forbidden extends \RuntimeException
{
    public function __construct($message = 'forbidden', $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
