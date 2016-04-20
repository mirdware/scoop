<?php
namespace Scoop\Validation;

class MinLength extends Rule
{
    public function __construct($fields, $min) {
        parent::__construct('minLength', $fields, array('min' => $min));
    }

    public function validate(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['min'] < $params['length'] ;
    }
}
