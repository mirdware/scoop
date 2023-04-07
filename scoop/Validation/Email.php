<?php

namespace Scoop\Validation;

class Email extends Rule
{
    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
