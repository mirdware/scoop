<?php

namespace Scoop;

final class View
{
    private static $stack = array();
    private $path;
    private $data;

    public function __construct($path)
    {
        $this->path = $path;
        $this->data = array();
    }
    public function add($key, $value = null)
    {
        if (is_array($key)) {
            $this->data += $key;
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
    }

    public function remove()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            unset($this->data[$arg]);
        }
        return $this;
    }

    public function render()
    {
        $request = Context::inject('Scoop\Http\Message\Server\Request');
        $environment = Context::inject('Scoop\Bootstrap\Environment');
        $router = Context::inject('Scoop\Http\Router');
        $heritage = new View\Heritage($environment);
        $helper = new View\Helper($request, $environment, $router, $heritage, $this->data);
        array_push(self::$stack, $helper);
        View\Service::inject('view', $helper);
        extract($this->data);
        require $heritage->getCompilePath($this->path);
        array_pop(self::$stack);
        if (!empty(self::$stack)) {
            View\Service::inject('view', end(self::$stack));
        }
        return $heritage->getContent();
    }
}
