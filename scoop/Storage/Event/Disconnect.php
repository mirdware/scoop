<?php
namespace Scoop\Storage\Event;

class Disconnect extends \Scoop\Event
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
