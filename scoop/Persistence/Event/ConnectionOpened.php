<?php

namespace Scoop\Persistence\Event;

class ConnectionOpened
{
    private $connection;
    private $time;

    public function __construct($connection)
    {
        $this->time = new \DateTime();
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getTime()
    {
        return $this->time;
    }
}
