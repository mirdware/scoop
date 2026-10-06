<?php

namespace Scoop;

final class View
{
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
        $context = View\Service::getContext();
        $injector = $context->getInjector();
        $request = $injector->get('Scoop\Http\Message\Server\Request');
        $router = $injector->get('Scoop\Http\Router');
        $bufferLevel = ob_get_level();
        $previous = null;
        try {
            $heritage = new View\Heritage($context);
            $helper = new View\Helper($request, $context, $router, $heritage, $this->data);
            $previous = View\Service::inject('view', $helper);
            extract($this->data, EXTR_SKIP);
            require $heritage->getCompilePath($this->path);
            $content = $heritage->getContent();
        } catch (\Exception $error) {
            $this->restoreRenderState($previous, $bufferLevel);
            throw $error;
        } catch (\Throwable $error) {
            $this->restoreRenderState($previous, $bufferLevel);
            throw $error;
        }
        $this->restoreRenderState($previous, $bufferLevel);
        return $content;
    }

    private function restoreRenderState($previous, $bufferLevel)
    {
        if ($previous) {
            View\Service::inject('view', $previous);
        }
        while (ob_get_level() > $bufferLevel) {
            if (!ob_end_clean()) {
                break;
            }
        }
    }
}
