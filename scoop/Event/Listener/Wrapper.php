<?php

namespace Scoop\Event\Listener;

class Wrapper {
    private $injector;
    private $listenerClass;
    private $method;
    private $middlewares;

    public function __construct(\Scoop\Container\Injector $injector, $listenerClass, $method, $middlewares) {
        $this->injector = $injector;
        $this->listenerClass = $listenerClass;
        $this->method = $method;
        $this->middlewares = $middlewares;
    }

    public function __invoke($event) {
        $handler = new \Scoop\Middleware\RequestHandler(
            $this->injector,
            $this->listenerClass,
            $this->method,
            $this->middlewares,
            array($event)
        );
        return $handler->handle($event);
    }
}
