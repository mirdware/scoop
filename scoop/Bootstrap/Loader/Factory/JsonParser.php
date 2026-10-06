<?php

namespace Scoop\Bootstrap\Loader\Factory;

class JsonParser
{
    private $context;

    public function __construct(\Scoop\Bootstrap\Environment $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        return new \Scoop\Bootstrap\Loader\JsonParser(
            $this->context->getStoragePath('cache/json')
        );
    }
}
