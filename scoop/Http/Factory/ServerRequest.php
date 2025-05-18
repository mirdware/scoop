<?php

namespace Scoop\Http\Factory;

class ServerRequest
{
    public function createFromGlobals()
    {
        return new \Scoop\Http\Message\Server\Request();
    }
}
