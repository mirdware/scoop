<?php

namespace Scoop\Http\Event;

class RequestFinished
{
    private $request;
    private $response;
    private $error;
    private $time;

    public function __construct($request, $response, $error)
    {
        $this->time = new \DateTime();
        $this->response = $response;
        $this->request = $request;
        $this->error = $error;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getTime()
    {
        return $this->time;
    }
}
