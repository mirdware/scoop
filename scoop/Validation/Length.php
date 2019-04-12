<?php
namespace Scoop\Validation;

class Length extends Rule
{
    protected $max;
    protected $min;
    protected $length;

    public function __construct($fields, $min, $max)
    {
        parent::__construct($fields);
        $this->max = $max;
        $this->min = $min;
    }

    public function validate($value)
    {
        $this->length = strlen($value);
        return $this->length >= $this->min && $this->length <= $this->max;
    }
}
