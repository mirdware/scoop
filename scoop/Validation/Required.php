<?php
namespace Scoop\Validation;

class Required extends Rule
{
    public function __construct($fields)
    {
        parent::__construct('required', $fields);
    }

    public function validate(&$params)
    {
        return !empty($params['value']);
    }
}
