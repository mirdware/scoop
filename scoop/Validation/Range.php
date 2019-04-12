<?php
namespace Scoop\Validation;

class Range extends Rule
{
    protected $min;
    protected $max;

    public function __construct($fields, $min, $max)
    {
        parent::__construct($fields);
        $this->min = $min;
        $this->max = $max;
    }

    public function validate($value)
    {
        if (is_numeric($value)) {
            return $value >= $this->min && $value <= $this->max;
        }
    }
}
