<?php
namespace Scoop\Validation;

class Number extends Rule
{
    public function __construct($fields)
    {
        parent::__construct($fields);
    }

    public function validate($value)
    {
        return is_numeric($value);
    }
}
