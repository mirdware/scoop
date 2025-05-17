<?php

namespace Scoop\Http\Handler;

class Request
{
    private $controller;
    private $middlewares;
    private $params;
    private $callable;

    public function __construct($controller, $callable, $route)
    {
        $this->middlewares = $route['middlewares'];
        $this->params = $route['params'];
        $this->callable = $callable;
        $this->controller = $controller;
    }

    public function handle($request)
    {
        if (empty($this->middlewares)) {
            $args = $this->getArguments($this->callable->getParameters(), $this->params, $request);
            return $this->callable->invokeArgs($this->controller, $args);
        }
        $middlewareDefinition = array_shift($this->middlewares);
        if (!class_exists($middlewareDefinition)) {
            throw new \RuntimeException("Middleware $middlewareDefinition not found");
        }
        if (!method_exists($middlewareDefinition, 'process')) {
            throw new \RuntimeException("Middleware $middlewareDefinition does not implement process method");
        }
        $middlewareInstance = \Scoop\Context::inject($middlewareDefinition);
        return $middlewareInstance->process($request, new Next($this));
    }

    private function getArguments($parameters, $params, $request)
    {
        $args = array();
        foreach ($parameters as $reflectionParam) {
            $paramName = $reflectionParam->getName();
            $paramClass = $reflectionParam->getClass();
            if ($paramClass !== null && $request !== null && is_object($request) && $paramClass->getName() === get_class($request)) {
                $args[] = $request;
            } elseif (isset($params[$paramName])) {
                $args[] = $params[$paramName];
            } elseif ($reflectionParam->isDefaultValueAvailable()) {
                $args[] = $reflectionParam->getDefaultValue();
            } elseif (!$reflectionParam->isOptional()) {
                throw new \Scoop\Http\Exception\NotFound("has '$paramName' missing");
            }
        }
        return $args;
    }
}
