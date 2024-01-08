<?php

namespace Scoop\Validation;

class Range extends Rule
{
    protected $min;
    protected $max;

    public function __construct($min, $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate($value)
    {
        if (is_numeric($value)) {
            return $value >= $this->min && $value <= $this->max;
        }
        return false;
    }
}
