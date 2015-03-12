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
        $this->instances[$class] = new $class();

        return $this;
    }

    public function single($class)
    {
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
            return $this->instances[$key];
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