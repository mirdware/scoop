<?php
namespace Scoop\Bootstrap;

abstract class Environment
{
    private $router;
    private $config;
    private static $sessionInit = false;

    public function __construct($configPath)
    {
        if (!self::$sessionInit) {
            self::$sessionInit = session_start();
        }
        define('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
        $this->config = require $configPath.'.php';
        $this->router = new \Scoop\IoC\Router($this->config['routes']);
        \Scoop\View\Helper::setAssets($this->get('assets'));
        \Scoop\Validator::setMessages($this->get('messages.error'));
        \Scoop\IoC\Service::register('config', $this);
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
        foreach ($interfaces as $interface => &$class) {
            \Scoop\IoC\Injector::bind($interface, $class);
        }
        return $this;
    }

    protected function registerServices($servicesPath)
    {
        $services = require $servicesPath.'.php';
        foreach ($services as $name => &$service) {
            \Scoop\IoC\Service::register($name, $service);
        }
        return $this;
    }

    protected function registerComponents($componentPath)
    {
        $components = require $componentPath.'.php';
        foreach ($services as $name => &$component) {
            \Scoop\View::registerComponent($name, $component);
        }
        return $this;
    }
}
