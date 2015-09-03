<?php
namespace Scoop\Bootstrap;

abstract class Environment
{
    private $router;
    private $config;

    public function getRouter()
    {
        return $this->router;
    }

    public function get($name)
    {
        return $this->config->get($name);
    }

    protected function setRouter(\Scoop\IoC\Router $router)
    {
        $this->router = $router;
        return $this;
    }

    protected function setConfig(Configuration $config)
    {
        $this->config = $config;
        return $this;
    }

    protected function bind($interface, $class)
    {
        \Scoop\IoC\Injector::bind($interface, $class);
        return $this;
    }

    protected function registerService($name, $class)
    {
        \Scoop\IoC\Service::register($name, $class);
        return $this;
    }

    public abstract function configure();
}
