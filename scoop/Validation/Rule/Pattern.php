<?php

namespace Scoop\Validation\Rule;

class Pattern extends \Scoop\Validation\Rule
{
    protected $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function validate($value)
    {
        return preg_match('/' . $this->pattern . '/', $value);
    }
}
