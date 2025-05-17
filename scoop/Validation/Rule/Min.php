<?php

namespace Scoop\Validation\Rule;

class Min extends \Scoop\Validation\Rule
{
    protected $min;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value)
    {
        if (is_numeric($value)) {
            return $value >= $this->min;
        }
        return false;
    }
}
