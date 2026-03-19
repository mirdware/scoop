<?php

namespace Scoop\Http\Handler;

class Request
{
    private $controller;
    private $middlewares;
    private $params;
    private $method;

    public function __construct($method, $route)
    {
        $this->middlewares = $route['middlewares'];
        $this->params = $route['params'];
        $this->controller = $route['controller'];
        $this->method = $method;
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
            method_exists($response, '__toString')
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

    private function processController($request)
    {
        $controller = $this->getController();
        $method = is_callable($controller) ? '__invoke' : $this->method;
        $controllerReflection = new \ReflectionClass($controller);
        if (!$controllerReflection->hasMethod($method)) {
            throw new \Scoop\Http\Exception\MethodNotAllowed("not implement $method method");
        }
        $callable = $controllerReflection->getMethod($method);
        $args = $this->getArguments($callable->getParameters(), $request);
        $response = $callable->invokeArgs($controller, $args);
        return $this->transformResponse($response);
    }

    private function getController()
    {
        $controller = $this->controller;
        if (is_array($controller)) {
            if (!isset($controller[$this->method])) {
                throw new \Scoop\Http\Exception\MethodNotAllowed("not implement {$this->method} method");
            }
            $controller = $controller[$this->method];
        }
        if (!class_exists($controller)) {
            throw new \Scoop\Http\Exception\NotFound();
        }
        return \Scoop\Context::inject($controller);
    }
}
