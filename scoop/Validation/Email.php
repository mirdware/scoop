<?php
namespace Scoop\Validation;

class Email extends Rule
{
    public function __construct($fields)
    {
        parent::__construct('email', $fields);
    }

    public function validate(&$params)
    {
        return filter_var($params['value'], FILTER_VALIDATE_EMAIL);
    }
}
