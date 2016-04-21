<?php
namespace Scoop\Validation;

class Equals extends Rule
{
    public function __construct($fields, $inputs) {
        parent::__construct('equals', $fields, array('inputs' => $inputs), true);
    }

    public function validate(&$params)
    {
        foreach ($params['inputs'] as $key => &$value) {
            if (empty($value)) {
                return true;
            }
            echo $value.'!=='.$params['value'];
            if ($value !== $params['value']) {
                $params['inputs'] = $key;
                return false;
            }
        }
        return true;
    }
}