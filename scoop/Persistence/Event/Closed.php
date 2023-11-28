<?php

namespace Scoop\Persistence\Event;

class Closed extends \Scoop\Event
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
