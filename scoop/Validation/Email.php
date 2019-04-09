<?php
namespace Scoop\Validation;

class Email extends Rule
{
    public function __construct($fields)
    {
        parent::__construct($fields);
    }

    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
