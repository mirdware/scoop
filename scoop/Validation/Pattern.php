<?php
namespace Scoop\Validation;

class Pattern extends Rule
{
    public function __construct($fields, $pattern, $mask = '')
    {
        parent::__construct('pattern', $fields, array('pattern' => $pattern, 'mask' => $mask));
    }

    public function validate(&$params)
    {
        return preg_match('/'.$params['pattern'].'/', $params['value']);
    }
}
