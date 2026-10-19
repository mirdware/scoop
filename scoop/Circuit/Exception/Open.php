<?php

namespace Scoop\Circuit\Exception;

class Open extends \RuntimeException
{
    private $key;
    private $remainingTime;

    public function __construct($key, $remainingTime)
    {
        parent::__construct('Circuit is open: ' . $key);
        $this->key = $key;
        $this->remainingTime = $remainingTime;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getRemainingTime()
    {
        return $this->remainingTime;
    }
}
