<?php

namespace Scoop\Http\Event;

class RequestFinished
{
    private $error;
    private $status;
    private $time;

    public function __construct($error, $status)
    {
        $this->time = new \DateTime();
        $this->error = $error;
        $this->status = $status;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function getTime()
    {
        return $this->time;
    }
}
