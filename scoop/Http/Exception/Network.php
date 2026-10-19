<?php

namespace Scoop\Http\Exception;

class Network extends Client
{
    private $request;

    public function __construct($message, $request, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
