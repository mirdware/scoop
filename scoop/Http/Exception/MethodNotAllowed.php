<?php

namespace Scoop\Http\Exception;

class MethodNotAllowed extends \BadMethodCallException
{
    private $method;

    public function __construct($message, $method, $previous = null)
    {
        parent::__construct($message, 405, $previous);
        $this->method = $method;
    }

    public function getContext()
    {
        return array('method' => $this->method);
    }
}
