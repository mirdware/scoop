<?php

namespace Scoop\Validation;

class Uppercase extends Rule
{
    public function validate($value)
    {
        return $value === strtoupper($value);
    }
}
