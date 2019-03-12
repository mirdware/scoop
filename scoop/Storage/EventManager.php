<?php
namespace Scoop\Storage;

class EventManager
{
    public function preConnect() {}

    public function posConnect(DBC $connection)
    {
        $connection->exec('SET NAMES \'utf8\'');
    }
}
