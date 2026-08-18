<?php

namespace Scoop\Container\Injector;

class Memory extends \Scoop\Container\Injector
{
    private $instances = array();
    private $singletons = array();

    public function contains($id)
    {
        return isset($this->instances[$id]) || isset($this->singletons[$id]);
    }

    public function clean()
    {
        $this->instances = array();
    }

    protected function getInstance($id)
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }
        return $this->instances[$id];
    }

    protected function setInstance($id, $scope, $instance)
    {
        if ($scope !== 'prototype') {
            if ($scope === 'singleton') {
                $this->singletons[$id] = $instance;
            } else {
                $this->instances[$id] = $instance;
            }
        }
    }
}
