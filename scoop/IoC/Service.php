<?php
namespace Scoop\IoC;

class Service
{
    private $services = array();

    public function register($key, $callback, $params = array())
    {
        if (is_string($callback)) {
            $params = array($callback, $params);
            $callback = array('\Scoop\IoC\Injector', 'create');
        }
        $this->services[$key] = array(
            'callback' => $callback,
            'params' => $params
        );
        return $this;
    }

    public function getInstance($key)
    {
        $serv = &$this->services[$key];
        if (!isset($serv['instance'])) {
            $serv['instance'] = call_user_func_array($serv['callback'], $serv['params']);
        }
        return $serv['instance'];
    }

    public function create($key)
    {
        $serv = &$this->services[$key];
        return call_user_func_array($serv['callback'], $serv['params']);
    }
}
