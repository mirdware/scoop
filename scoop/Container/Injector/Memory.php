<?php

namespace Scoop\Container\Injector;

class Memory extends \Scoop\Container\Injector
{
    private $instances = array();

    public function has($id)
    {
        return isset($this->instances[$id]);
    }

    protected function getInstance($id)
    {
        return $this->instances[$id];
    }

    protected function setInstance($id, $instance)
    {
        $this->instances[$id] = $instance;
    }
}
