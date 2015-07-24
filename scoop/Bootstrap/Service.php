<?php
namespace Scoop\Bootstrap;

class Service implements IoC
{
    public $services = array();

    public function register($key, $callback, $params = array())
    {
        if (is_string($callback)) {
            $params = array($callback, $params);
            $callback = array($this, 'construct');
        }

        $this->services[$key] = array(
            'callback' => $callback,
            'params' => $params
        );
    }

    public function single($key)
    {
        $serv = &$this->services[$key]; 
        if (!isset($serv['instance'])) {
            $serv['instance'] = call_user_func_array($serv['callback'], $serv['params']);
        }
        return $serv['instance'];
    }

    public function instance($key)
    {
        $serv = &$this->services[$key];
        return call_user_func_array($serv['callback'], $serv['params']);
    }

    private function construct($class, $params)
    {
        $reflectionClass = new \ReflectionClass($class);
        if ($reflectionClass->getConstructor()) {
            foreach ($params as &$value) {
                if (is_string($value) && strpos($value, '@') === 0) {
                    $value = $this->instance(substr($value, 1));
                } elseif (class_exists($value)) {
                    $value = new $value();
                }
            }
            return $reflectionClass->newInstance($params);
        }
        return $reflectionClass->newInstanceWithoutConstructor();
    }
}
