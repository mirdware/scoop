<?php
namespace Scoop\Validation;

class Length extends Rule
{
    public function __construct($fields, $min, $max) {
        parent::__construct('length', $fields, array('min' => $min, 'max' => $max));
    }

    public function validate(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['length'] >= $params['min'] && $params['length'] <= $params['max'];
    }
}
