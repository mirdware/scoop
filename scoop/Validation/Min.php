<?php

namespace Scoop\Validation;

class Min extends Rule
{
    protected $min;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value)
    {
        if (is_numeric($value)) {
            return $value <= $this->min;
        }
        return false;
    }
}
