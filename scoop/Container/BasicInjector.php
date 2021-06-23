<?php
namespace Scoop\Container;

class BasicInjector extends Injector
{
    private $instances = array();

    public function __construct($environment)
    {
        parent::__construct($environment);
    }

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
