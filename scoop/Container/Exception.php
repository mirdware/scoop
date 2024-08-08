<?php

namespace Scoop\Container;

class Exception extends \RuntimeException
{
    public function __construct($msg, $code = 1100)
    {
        parent::__construct($msg, $code, $this);
    }
}
