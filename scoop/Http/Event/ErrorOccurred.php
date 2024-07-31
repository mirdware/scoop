<?php

namespace Scoop\Http\Event;

class ErrorOccurred
{
    private $exception;
    private $status;

    public function __construct($exception, $status)
    {
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
}