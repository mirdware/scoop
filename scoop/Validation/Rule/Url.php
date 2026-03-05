<?php

namespace Scoop\Validation\Rule;

class Url extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }
}
