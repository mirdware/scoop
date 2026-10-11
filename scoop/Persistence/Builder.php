<?php

namespace Scoop\Persistence;

class Builder
{
    private $context;
    private $connection;
    private $instances = array();

    public function __construct(\Scoop\Context $context, Connection $connection)
    {
        $this->context = $context;
        $this->connection = $connection;
    }

    public function build($table, $alias = '')
    {
        $key = "$table:$alias";
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = new Builder\Query($this->context, $this->connection, $table, $alias);
        }
        return $this->instances[$key];
    }
}
