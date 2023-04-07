<?php

namespace Scoop\Validation;

class Number extends Rule
{
    public function validate($value)
    {
        return is_numeric($value);
    }
}
