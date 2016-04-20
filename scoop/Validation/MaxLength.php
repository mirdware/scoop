<?php
namespace Scoop\Validation;

class MaxLength extends Rule
{
    public function __construct($fields, $max) {
        parent::__construct('maxLength', $fields, array('max' => $max));
    }

    public function validate(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['length'] < $params['max'];
    }
}
