<?php

namespace Scoop\Validation;

class Max extends Rule
{
    protected $max;

    public function __construct($max)
    {
        $this->max = $max;
    }

    public function validate($value)
    {
        if (is_numeric($value)) {
            return $value <= $this->max;
        }
        return false;
    }
}
