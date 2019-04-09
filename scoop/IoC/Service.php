<?php
namespace Scoop\IoC;

class Service
{
    private $services = array();

    public function register($key, $callback, $params)
    {
        if (!is_callable($callback)) {
            if (!is_string($callback)) {
                $this->services[$key]['instance'] = $callback;
                return $this;
            }
            $params = array($callback, $params);
            $callback = array(\Scoop\Context::getInjector(), 'create');
        }
        $this->services[$key] = array(
            'callback' => $callback,
            'params' => $params
        );
        return $this;
    }

    public function get($key)
    {
        $serv = $this->services[$key];
        if (!isset($serv['instance'])) {
            $serv['instance'] = call_user_func_array($serv['callback'], $serv['params']);
        }
        return $serv['instance'];
    }
}
