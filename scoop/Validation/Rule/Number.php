<?php

namespace Scoop\Validation\Rule;

class Number extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return is_numeric($value);
    }
}
