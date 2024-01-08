<?php

namespace Scoop\Validation;

class MinLength extends Rule
{
    protected $min;
    protected $length;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value)
    {
        $this->length = strlen($value);
        return $this->min <= $this->length;
    }
}
