<?php
namespace Scoop\Bootstrap;

class Environment
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
        $config = require $configPath.'.php';
        $this->config = $config;
        $this->configure();
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

    protected function bind($interfaces)
    {
        $injector = \Scoop\Context::getInjector();
        foreach ($interfaces as $interface => &$class) {
            $injector->bind($interface, $class);
        }
        return $this;
    }

    protected function registerServices($services)
    {
        foreach ($services as $name => &$service) {
            \Scoop\Context::registerService($name, $service);
        }
        return $this;
    }

    protected function registerComponents($components)
    {
        foreach ($components as $name => &$component) {
            \Scoop\View::registerComponent($name, $component);
        }
        return $this;
    }

    protected function configure() {
        $config = $this->config;
        $this->router = new \Scoop\IoC\Router($config['routes']);
        \Scoop\Validator::setMessages($this->get('messages.error'));
        if (isset($config['providers'])) {
            $this->bind($config['providers']);
        }
        if (isset($config['components'])) {
            $this->registerComponents($config['components']);
        }
        $services = array('config' => $this);
        if (isset($config['services'])) {
            $services += $config['services'];
        }
        $this->registerServices($services);
        return $this;
    }

    public function __get($name)
    {
        if ($name === 'router') {
            return $this->router;
        }
    }
}
