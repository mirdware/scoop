<?php

namespace Scoop\Validation\Rule;

class MaxLength extends \Scoop\Validation\Rule
{
    protected $max;
    protected $length;

    public function __construct($max)
    {
        $this->max = $max;
    }

    public function validate($value)
    {
        $value = $value === null ? '' : $value;
        $this->length = strlen($value);
        return $this->length <= $this->max;
    }
}
