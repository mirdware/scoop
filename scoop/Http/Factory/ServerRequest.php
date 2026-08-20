<?php

namespace Scoop\Http\Factory;

class ServerRequest
{
    public function createServerRequest($method, $uri, $serverParams)
    {
        return new \Scoop\Http\Message\Server\Request(new \Scoop\Http\Message\URI($uri), '', $method, array(), array(), array(), $serverParams);
    }

    public function createFromGlobals()
    {
        return new \Scoop\Http\Message\Server\Request();
    }
}
