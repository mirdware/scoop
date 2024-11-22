<?php

namespace Scoop\Http\Exception;

class Proxy
{
    private $ex;
    private $msg;

    public function __construct(\Exception $ex, $msg)
    {
        $this->ex = $ex;
        $this->msg = $msg;
    }

    public function getMessage()
    {
        return $this->msg;
    }

    public function getCode()
    {
        return $this->ex->getCode();
    }

    public function getFile()
    {
        return $this->ex->getFile();
    }

    public function getLine()
    {
        return $this->ex->getLine();
    }

    public function getTrace()
    {
        return $this->ex->getTrace();
    }

    public function getTraceAsString()
    {
        return $this->ex->getTraceAsString();
    }

    public function getPrevious()
    {
        return $this->ex->getPrevious();
    }

    public function __toString()
    {
        return $this->ex->__tostring();
    }
}
