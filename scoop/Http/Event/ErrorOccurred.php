<?php

namespace Scoop\Http\Event;

class ErrorOccurred
{
    private $exception;
    private $status;
    private $time;

    public function __construct($exception, $status)
    {
        $this->time = new \DateTime();
        $this->exception = $exception;
        $this->status = $status;
    }

    public function getError()
    {
        return $this->exception;
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
