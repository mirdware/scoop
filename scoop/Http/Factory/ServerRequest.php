<?php

namespace Scoop\Http\Factory;

class ServerRequest
{
    private $router;

    public function __construct(\Scoop\Http\Router $router)
    {
        $this->router = $router;
    }
    public function createServerRequest($method, $uri, $serverParams)
    {
        return new \Scoop\Http\Message\Server\Request(
            $this->router,
            new \Scoop\Http\Message\URI($uri),
            '',
            $method,
            array(),
            array(),
            array(),
            $serverParams
        );
    }

    public function createFromGlobals()
    {
        return new \Scoop\Http\Message\Server\Request($this->router);
    }
}
