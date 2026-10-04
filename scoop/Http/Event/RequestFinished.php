<?php

namespace Scoop\Http\Event;

class RequestFinished
{
    private $time;

    public function __construct()
    {
        $this->time = new \DateTime();
    }

    public function getTime()
    {
        return $this->time;
    }
}
