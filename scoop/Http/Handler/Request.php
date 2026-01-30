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
            $args = $this->getArguments($request);
            $response = $this->callable->invokeArgs($this->controller, $args);
            return $this->transformResponse($response);
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

    private function getArguments($request)
    {
        $parameters = $this->callable->getParameters();
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
        if (isset($this->params[$paramName])) {
            return $this->params[$paramName];
        }
        if ($reflectionParam->isDefaultValueAvailable()) {
            return $reflectionParam->getDefaultValue();
        }
        throw new \Scoop\Http\Exception\NotFound("has '$paramName' missing");
    }

    private function transformResponse($response)
    {
        if ($response instanceof \Scoop\Http\Message\Response) {
            return $response;
        }
        if ($response instanceof \Scoop\View) {
            return new \Scoop\Http\Message\Response(
                200,
                array('Content-Type' => 'text/html'),
                $response->render()
            );
        }
        if ($response === null || $response === '') {
            return new \Scoop\Http\Message\Response();
        }
        if (
            is_scalar($response) ||
            is_object($response) &&
            method_exists($response, '_toString')
        ) {
            return new \Scoop\Http\Message\Response(
                200,
                array('Content-Type' => 'text/plain'),
                $response
            );
        }
        return new \Scoop\Http\Message\Response(
            200,
            array('Content-Type' => 'application/json'),
            json_encode($response)
        );
    }
}
