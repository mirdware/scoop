<?php

namespace Scoop\Validation\Rule;

class MinLength extends \Scoop\Validation\Rule
{
    protected $min;
    protected $length;

    public function __construct($min)
    {
        $this->min = $min;
    }

    public function validate($value)
    {
        $value = $value === null ? '' : $value;
        $this->length = strlen($value);
        return $this->min <= $this->length;
    }
}
