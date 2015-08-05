<?php
namespace Scoop\IoC;

class Router
{
    private $routes = array();
    private $instances = array();
    private static $route;

    public function register($route, $class = null)
    {
        if ($class === null) {
            $route = require $route.'.php';
            $this->load($route);
            return $this;
        }

        $this->routes[$class] = $route;
        return $this;
    }

    public function route($route)
    {
        self::$route = $route;
        $matches = array_filter($this->routes, function ($route) {
            return strpos(self::$route, $route) === 0;
        });

        if ($matches) {
            asort($matches);
            $key = array_keys($matches);
            $key = array_pop($key);
            return $this->getInstance($key);
        }
    }

    public function getInstance($class)
    {
        if (!isset($this->instances[$class])) {
            if (get_parent_class($class) !== 'Scoop\Controller') {
                throw new \Exception('The '.$class.' class is not an instance of controller');
            }
            $this->instances[$class] = Injector::create($class);
            $this->instances[$class]->setRouter($this);
        }
        return $this->instances[$class];
    }

    public function getURL($class)
    {
        return $this->routes[$class];
    }

    private function load($array, $oldRoute = '')
    {
        foreach ($array as $route => $class) {
            $currentRoute = $oldRoute.$route;
            if (is_array($class)) {
                $this->load($class, $currentRoute);
            } else {
                $this->register($currentRoute, $class);
            }
        }
    }
}
