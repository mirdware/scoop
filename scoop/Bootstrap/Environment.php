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
        $this->config = require $configPath.'.php';
    }

    public function get($name)
    {
        $name = explode('.', $name);
        $res = $this->config;
        foreach ($name as $key) {
            if (!isset($res[$key])) return null;
            $res = $res[$key];
        }
        return $res;
    }

    public function route($url)
    {
        $this->configure();
        return $this->router->route($url);
    }

    public function getURL($args)
    {
        if (empty($args)) {
            return $this->router->getCurrentRoute();
        }
        return $this->router->getURL(array_shift($args), $args);
    }

    protected function configure() {
        \Scoop\Validator::setMessages((Array) $this->get('messages.error'));
        \Scoop\Validator::addRule((Array) $this->get('validators'));
        $this->router = new \Scoop\IoC\Router((Array) $this->get('routes'));
        $this->bind((Array) $this->get('providers'));
        $this->registerComponents((Array) $this->get('components'));
        $services = (Array) $this->get('services');
        $services += array('config' => $this, 'request' => new \Scoop\Http\Request());
        $this->registerServices($services);
        return $this;
    }

    protected function bind($interfaces)
    {
        $injector = \Scoop\Context::getInjector();
        foreach ($interfaces as $interface => $class) {
            $injector->bind($interface, $class);
        }
        return $this;
    }

    protected function registerServices($services)
    {
        foreach ($services as $name => $service) {
            is_array($service) ?
                \Scoop\Context::registerService($name, array_shift($service), $service) :
                \Scoop\Context::registerService($name, $service);
        }
        return $this;
    }

    protected function registerComponents($components)
    {
        foreach ($components as $name => $component) {
            \Scoop\View::registerComponent($name, $component);
        }
        return $this;
    }
}
