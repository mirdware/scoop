<?php

namespace Scoop\Http\Exception;

class Unprocessable extends \RuntimeException
{
    private $response;

    public function __construct(\Scoop\Http\Message\Response $response, $previous = null)
    {
        $this->response = $response;
        parent::__construct('Unprocessable content', 400, $previous);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
