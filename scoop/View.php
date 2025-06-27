<?php

namespace Scoop;

final class View
{
    private $viewPath;
    private $viewData;
    private $componentData;

    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
        $this->viewData = array();
    }
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData += $key;
            return $this;
        }
        $this->viewData[$key] = $value;
        return $this;
    }

    public function remove($keys = null)
    {
        if (!$keys) {
            $this->viewData = array();
            return $this;
        }
        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($this->viewData[$key]);
            }
            return $this;
        }
        unset($this->viewData[$keys]);
        return $this;
    }

    public function transfer($data)
    {
        $this->componentData = $data;
        return $this;
    }

    public function render()
    {
        $request = Context::inject('Scoop\Http\Message\Server\Request');
        $environment = Context::inject('Scoop\Bootstrap\Environment');
        $router = Context::inject('Scoop\Http\Router');
        $heritage = new View\Heritage($environment);
        $helper = new View\Helper($request, $environment, $router, $heritage, $this->componentData);
        View\Service::inject('view', $helper);
        extract($this->viewData);
        require $heritage->getCompilePath($this->viewPath);
        return $heritage->getContent();
    }
}
