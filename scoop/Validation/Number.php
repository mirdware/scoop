<?php
namespace Scoop\Validation;

class Number extends Rule
{
    public function __construct($fields)
    {
        parent::__construct('number', $fields);
    }

    public function validate(&$params)
    {
        return is_numeric($params['value']);
    }
}
