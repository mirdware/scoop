<?php

namespace Scoop\Validation\Rule;

class Email extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
