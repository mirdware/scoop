<?php
namespace Scoop\Validation;

class MaxLength extends Rule
{
    protected $max;
    protected $length;

    public function __construct($fields, $max)
    {
        parent::__construct($fields);
        $this->max = $max;
    }

    public function validate($value)
    {
        $this->length = strlen($value);
        return $this->length <= $this->max;
    }
}
