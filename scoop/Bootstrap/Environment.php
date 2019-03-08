<?php
namespace Scoop\Bootstrap;

abstract class Environment
{
    private static $sessionInit = false;
    private $router;
    private $config;

    public function __construct($configPath)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        define('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
        $this->config = require $configPath.'.php';
        $this->router = new \Scoop\IoC\Router($this->config['routes']);
        \Scoop\Validator::setMessages($this->get('messages.error'));
        \Scoop\Context::registerService('config', $this);
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function get($name)
    {
        $name = explode('.', $name);
        $res = $this->config;
        foreach ($name as $key) {
            if (!isset($res[$key])) {
                return false;
            }
            $res = $res[$key];
        }
        return $res;
    }

    protected function bind($interfacesPath)
    {
        $interfaces = require $interfacesPath.'.php';
        $injector = \Scoop\Context::getInjector();
        foreach ($interfaces as $interface => &$class) {
            $injector->bind($interface, $class);
        }
        return $this;
    }

    protected function registerServices($servicesPath)
    {
        $services = require $servicesPath.'.php';
        foreach ($services as $name => &$service) {
            \Scoop\Context::registerService($name, $service);
        }
        return $this;
    }

    protected function registerComponents($componentPath)
    {
        $components = require $componentPath.'.php';
        foreach ($components as $name => &$component) {
            \Scoop\View::registerComponent($name, $component);
        }
        return $this;
    }
}
