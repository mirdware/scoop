<?php

namespace Scoop\Validation\Rule;

class Uppercase extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return $value === strtoupper($value);
    }
}
