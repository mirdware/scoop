<?php
namespace Scoop;

class Validator
{
    const SIMPLE_VALIDATION = 0;
    const FULL_VALIDATION = 1;
    const DEFAULT_MSG = 'Invalid field';
    private static $msg;
    protected $data;
    private $rules;
    private $typeValidation;

    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->rules = array();
        $this->typeValidation = $typeValidation;
    }

    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    public function validate($data)
    {
        $this->data = &$data;
        $errors = array();

        foreach ($this->rules as &$rule) {
            if (is_array($rule['fields'])) {
                foreach ($rule['fields'] as $key => &$field) {
                    $rule['params'] = array('label' => $field) + $rule['params'];
                    if (!is_numeric($key)) {
                        $field = $key;
                    }
                    $this->executeRule($rule['rule'], $field, $rule['params'], $errors);
                }
            } else {
                $rule['params'] = array('label' => $rule['fields']) + $rule['params'];
                $this->executeRule($rule['rule'], $rule['fields'], $rule['params'], $errors);
            }
        }
        return $errors;
    }

    public function required($fields)
    {
        return $this->addRule('required', $fields);
    }

    public function length($fields, $min, $max)
    {
        return $this->addRule('length', $fields, array('min' => $min, 'max' => $max));
    }

    public function maxLength($fields, $max)
    {
        return $this->addRule('maxLength', $fields, array('max' => $max));
    }

    public function minLength($fields, $min)
    {
        return $this->addRule('minLength', $fields, array('min' => $min));
    }

    public function range($fields, $min, $max)
    {
        return $this->addRule('range', $fields, array('min' => $min, 'max' => $max));
    }

    public function max($fields, $max)
    {
        return $this->addRule('max', $fields, array('max' => $max));
    }

    public function min($fields, $min)
    {
        return $this->addRule('min', $fields, array('min' => $min));
    }

    public function number($fields)
    {
        return $this->addRule('number', $fields);
    }

    public function email($fields)
    {
        return $this->addRule('email', $fields);
    }

    public function pattern($fields, $pattern, $mask = '')
    {
        return $this->addRule('pattern', $fields, array('pattern' => $pattern, 'mask' => $mask));
    }

    protected function addRule($rule, $fields, $params = array())
    {
        $this->rules[] = array('rule' => $rule, 'fields' => $fields, 'params' => $params);
        return $this;
    }
    
    protected function validateRequired(&$params)
    {
        return !empty($params['value']);
    }

    protected function validateLength(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['length'] > $params['min'] && $params['length'] < $params['max'];
    }

    protected function validateMaxLength(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['length'] < $params['max'];
    }

    protected function validateMinLength(&$params)
    {
        $params['length'] = strlen($params['value']);
        return $params['length'] > $params['min'];
    }

    protected function validateRange(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] > $params['min'] && $params['value'] < $params['max'];
        }
    }

    protected function validateMax(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] < $params['max'];
        }
    }

    protected function validateMin(&$params)
    {
        if (is_numeric($params['value'])) {
            return $params['value'] < $params['min'];
        }
    }

    protected function validateNumber(&$params)
    {
        return is_numeric($params['value']);
    }

    protected function validateEmail(&$params)
    {
        return filter_var($params['value'], FILTER_VALIDATE_EMAIL);
    }

    protected function validatePattern(&$params)
    {
        return preg_match('/'.$params['pattern'].'/', $params['value']);
    }

    private function executeRule($rule, $field, $params, &$errors)
    {
        if ($rule !== 'required' && !isset($this->data[$field])){
            return;
        }
        $method = 'validate'.ucfirst($rule);
        $params = array('value' => $this->data[$field]) + $params;

        if ($this->typeValidation === self::SIMPLE_VALIDATION) {
            if (!isset($errors[$field]) && !$this->$method($params)) {
                $errors[$field] = self::formatMessage($rule, $params);
            }
        } elseif (!$this->$method($params)) {
            $errors[$field][] = self::formatMessage($rule, $params);
        }
    }

    private static function formatMessage($rule, $params)
    {
        if (isset(self::$msg[$rule])) {
            $keys = array_keys($params);
            foreach ($keys as &$key) {
                $key = '{'.$key.'}';
            }
            return str_replace($keys, $params, self::$msg[$rule]);
        }
        return self::DEFAULT_MSG;
    }
}
