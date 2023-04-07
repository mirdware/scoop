<?php

namespace Scoop\Validation;

class Required extends Rule
{
    public function validate($value)
    {
        return $value !== '' && $value != null;
    }
}
