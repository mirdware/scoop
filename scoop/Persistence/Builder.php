<?php

namespace Scoop\Persistence;

class Builder
{
    private $connectionName;
    private $instances = array();

    public function __construct($connectionName = 'default')
    {
        $this->connectionName = $connectionName;
    }

    public function build($table, $alias = '')
    {
        $key = "$table:$alias";
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = new SQO($table, $alias, $this->connectionName);
        }
        return $this->instances[$key];
    }
}
