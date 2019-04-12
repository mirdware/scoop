<?php
namespace Scoop\Validation;

class Min extends Rule
{
    protected $min;

    public function __construct($fields, $min)
    {
        parent::__construct($fields);
        $this->min = $min;
    }

    public function validate($value)
    {
        if (is_numeric($value)) return $value <= $this->min;
    }
}
