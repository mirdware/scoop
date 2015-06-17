<?php
namespace Scoop\Bootstrap;

class Router implements IoC
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

    public function single($class)
    {
        if (!isset($this->instances[$class])) {
            if (get_parent_class($class) !== 'Scoop\Controller') {
                throw new \Exception('The '.$class.' class is not an instance of controller');
            }
            $this->instances[$class] = new $class();
            $this->instances[$class]->setRouter($this);
        }
        return $this->instances[$class];
    }

    public function instance($route)
    {
        self::$route = $route;
        $matches = array_filter($this->routes, function ($route) {
            return strpos(self::$route, $route) === 0;
        });

        if ($matches) {
            asort($matches);
            $key = array_keys($matches);
            $key = array_pop($key);
            return $this->single($key);
        }
    }

    public function url($class)
    {
        return $this->routes[$class];
    }

    private function load ($array, $oldRoute = '')
    {
        foreach ($array as $route => $class) {
            $currentRoute = $oldRoute.$route;
            if (is_array($class)) {
                $this->load($class, $currentRoute);
                continue;
            }
            $this->register($currentRoute, $class);
        }
    }
}
