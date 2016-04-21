<?php
namespace Scoop\Validation;

class Max extends Rule
{
    public function __construct($fields, $max) {
        parent::__construct('max', $fields, array('max' => $max));
    }

    public function validate(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] <= $params['max'];
        }
    }
}
