<?php

namespace Scoop\Validation\Rule;

class Required extends \Scoop\Validation\Rule
{
    public function validate($value)
    {
        return $value !== '' && $value != null;
    }
}
