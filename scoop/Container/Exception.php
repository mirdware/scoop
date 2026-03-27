<?php

namespace Scoop\Container;

class Exception extends \RuntimeException
{
    public function __construct($msg, $code = 1100, $previous = null)
    {
        parent::__construct($msg, $code, $previous);
    }
}
