<?php

namespace Scoop\Http\Handler;

class Request
{
    private $controller;
    private $middlewares;
    private $params;
    private $method;
    private $transformer;

    public function __construct($controller, $method, $middlewares, $params, $transformer = null)
    {
        $this->middlewares = $middlewares;
        $this->params = $params;
        $this->controller = $controller;
        $this->method = $method;
        $this->transformer = is_callable($transformer) ? $transformer : null;
    }

    public function handle($request)
    {
        if (empty($this->middlewares)) {
            return $this->processController($request);
        }
        $middlewareInstance = \Scoop\Context::inject(array_shift($this->middlewares));
        if (!method_exists($middlewareInstance, 'process')) {
            $className = get_class($middlewareInstance);
            throw new \RuntimeException("Middleware $className does not implement process method");
        }
        return $middlewareInstance->process($request, new Next($this));
    }

    private function getArguments($parameters, $request)
    {
        $args = array();
        $hasType = empty($parameters) ? false : method_exists($parameters[0], 'getType');
        foreach ($parameters as $reflectionParam) {
            $paramClass = $hasType ? $reflectionParam->getType() : $reflectionParam->getClass();
            $args[] = $paramClass !== null &&
            $paramClass->getName() === get_class($request) ?
            $request :
            $this->getArgument($reflectionParam);
        }
        return $args;
    }

    private function getArgument($reflectionParam)
    {
        $paramName = $reflectionParam->getName();
        if (array_key_exists($paramName, $this->params)) {
            return $this->params[$paramName];
        }
        $position = $reflectionParam->getPosition();
        if (array_key_exists($position, $this->params)) {
            return $this->params[$position];
        }
        if ($reflectionParam->isDefaultValueAvailable()) {
            return $reflectionParam->getDefaultValue();
        }
        throw new \InvalidArgumentException("Required parameter '$paramName' at position $position is missing.");
    }

    private function processController($request)
    {
        $controller = \Scoop\Context::inject($this->controller);
        $controllerReflection = new \ReflectionClass($controller);
        if (!$controllerReflection->hasMethod($this->method)) {
            throw new \BadMethodCallException("{$this->controller} does not implement {$this->method} method");
        }
        $callable = $controllerReflection->getMethod($this->method);
        $args = $this->getArguments($callable->getParameters(), $request);
        $response = $callable->invokeArgs($controller, $args);
        if ($this->transformer) {
            $response = call_user_func($this->transformer, $response);
        }
        return $response;
    }
}
