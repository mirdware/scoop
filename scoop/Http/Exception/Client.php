<?php

namespace Scoop\Http\Exception;

abstract class Client extends \RuntimeException
{
    abstract public function getRequest();
}
