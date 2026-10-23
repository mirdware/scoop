<?php

namespace Scoop\Validation\Rule;

class Uppercase extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        if ($value === null) {
            return false;
        }
        return $value === strtoupper($value);
    }
}
