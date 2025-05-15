<?php

namespace Scoop\Http\Message\Factory;

class Request
{
    public function createFromGlobals()
    {
        return new \Scoop\Http\Message\Server\Request();
    }
}
