<?php
namespace Scoop\Validation;

class Required extends Rule
{
    public function __construct($fields)
    {
        parent::__construct($fields);
    }

    public function validate($value)
    {
        return !empty($value);
    }
}
