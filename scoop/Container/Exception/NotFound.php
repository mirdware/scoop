<?php

namespace Scoop\Container\Exception;

class NotFound extends \Scoop\Container\Exception
{
    public function __construct($msg, $previous = null)
    {
        parent::__construct($msg, 1104, $previous);
    }
}
