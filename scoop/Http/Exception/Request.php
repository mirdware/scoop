<?php

namespace Scoop\Http\Exception;

class Request extends Client
{
    private $request;

    public function __construct($message, \Scoop\Http\Message\Request $request, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
