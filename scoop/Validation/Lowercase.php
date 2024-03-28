<?php

namespace Scoop\Validation;

class Lowercase extends Rule
{
    public function validate($value)
    {
        return $value === strtolower($value);
    }
}
