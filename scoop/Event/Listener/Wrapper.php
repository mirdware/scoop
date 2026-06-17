<?php

namespace Scoop\Event\Listener;

class Wrapper {
    private $listenerClass;
    private $method;
    private $middlewares;

    public function __construct($listenerClass, $method, $middlewares) {
        $this->listenerClass = $listenerClass;
        $this->method = $method;
        $this->middlewares = $middlewares;
    }

    public function __invoke($event) {
        $handler = new \Scoop\Middleware\RequestHandler(
            $this->listenerClass,
            $this->method,
            $this->middlewares,
            array($event)
        );
        return $handler->handle($event);
    }
}
