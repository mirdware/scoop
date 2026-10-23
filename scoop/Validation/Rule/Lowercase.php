<?php

namespace Scoop\Validation\Rule;

class Lowercase extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        if ($value === null) {
            return false;
        }
        return $value === strtolower($value);
    }
}
