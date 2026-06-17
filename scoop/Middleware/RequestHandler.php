<?php

namespace Scoop\Middleware;

class RequestHandler
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
        $this->transformer = $transformer;
    }

    public function handle($request)
    {
        if (empty($this->middlewares)) {
            return $this->processController($request);
        }
        $middlewareInstance = \Scoop\Context::inject(array_shift($this->middlewares));
        if (!method_exists($middlewareInstance, 'process')) {
            $className = get_class($middlewareInstance);
            throw new \BadMethodCallException("Middleware $className does not implement process method");
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
        $missingParameterException = new \InvalidArgumentException("Required parameter '$paramName' at position $position is missing");
        if (method_exists($this->transformer, 'transformMissingParameterException')) {
            $missingParameterException = $this->transformer->transformMissingParameterException($missingParameterException);
        }
        throw $missingParameterException;
    }

    private function processController($request)
    {
        $controller = \Scoop\Context::inject($this->controller);
        $controllerReflection = new \ReflectionClass($controller);
        if (!$controllerReflection->hasMethod($this->method)) {
            $missingMethodException = new \BadMethodCallException("{$this->controller} does not implement {$this->method} method");
            if (method_exists($this->transformer, 'transformMissingMethodException')) {
                $missingMethodException = $this->transformer->transformMissingMethodException($missingMethodException, $this->method);
            }
            throw $missingMethodException;
        }
        $callable = $controllerReflection->getMethod($this->method);
        $args = $this->getArguments($callable->getParameters(), $request);
        $response = $callable->invokeArgs($controller, $args);
        if (method_exists($this->transformer, 'transformResponse')) {
            $response = $this->transformer->transformResponse($response);
        }
        return $response;
    }
}
