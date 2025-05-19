<?php

namespace Scoop\Http\Handler;

class Next
{
    private $delegate;
    private $called = false;

    public function __construct($delegate)
    {
        $this->delegate = $delegate;
    }

    public function handle($request)
    {
        if ($this->called) {
            throw new \LogicException(
                'The next handler in the middleware chain has already been called. A middleware should only call once.'
            );
        }
        $this->called = true;
        return $this->delegate->handle($request);
    }
}
