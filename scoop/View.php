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
            $previous = View\Service::takeSnapshot();
            View\Service::inject('view', $helper);
            $content = $this->getContent($heritage);
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

    private function getContent($__heritage_)
    {
        extract($this->data, EXTR_SKIP);
        require $__heritage_->getCompilePath($this->path);
        return $__heritage_->getContent();
    }

    private function restoreRenderState($previous, $bufferLevel)
    {
        if ($previous !== null) {
            View\Service::restore($previous);
        }
        while (ob_get_level() > $bufferLevel) {
            if (!ob_end_clean()) {
                break;
            }
        }
    }
}
