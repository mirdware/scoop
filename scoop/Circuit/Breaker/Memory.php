<?php

namespace Scoop\Circuit\Breaker;

class Memory extends \Scoop\Circuit\Breaker
{
    private $states = array();

    protected function synchronize($key, callable $callback)
    {
        $state = isset($this->states[$key]) ? clone $this->states[$key] : null;
        $state = call_user_func($callback, $state);
        $this->states[$key] = $state;
        return $state;
    }
}
