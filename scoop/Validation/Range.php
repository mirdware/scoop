<?php
namespace Scoop\Validation;

class Range extends Rule
{
    public function __construct($fields, $min, $max) {
        parent::__construct('range', $fields, array('min' => $min, 'max' => $max));
    }

    public function validate(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] >= $params['min'] && $params['value'] <= $params['max'];
        }
    }
}
