<?php
namespace Scoop\Validation;

class Min extends Rule
{
    public function __construct($fields, $min) {
        parent::__construct('min', $fields, array('min' => $min));
    }

    public function validate(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] <= $params['min'];
        }
    }
}
