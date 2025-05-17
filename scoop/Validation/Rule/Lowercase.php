<?php

namespace Scoop\Validation\Rule;

class Lowercase extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return $value === strtolower($value);
    }
}
