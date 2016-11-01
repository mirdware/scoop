<?php
namespace Scoop;

class Validator
{
    const SIMPLE_VALIDATION = 0;
    const FULL_VALIDATION = 1;
    const DEFAULT_MSG = 'Invalid field';
    private static $msg = array();
    private static $customRules = array(
        'required' => '\Scoop\Validation\Required',
        'length' => '\Scoop\Validation\Length',
        'email' => '\Scoop\Validation\Email',
        'max' => '\Scoop\Validation\Max',
        'maxLength' => '\Scoop\Validation\MaxLength',
        'min' => '\Scoop\Validation\Min',
        'minLength' => '\Scoop\Validation\MinLength',
        'number' => '\Scoop\Validation\Number',
        'pattern' => '\Scoop\Validation\Pattern',
        'range' => '\Scoop\Validation\Range',
        'equals' => '\Scoop\Validation\Equals'
    );
    private $rules = array();
    private $data;
    private $errors;
    private $typeValidation;

    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->typeValidation = $typeValidation;
    }

    public function validate($data)
    {
        $this->data = &$data;
        $this->errors = array();
        foreach ($this->rules as &$rule) {
            $fields = $rule->getFields();
            if (is_array($fields)) {
                foreach ($fields as $key => &$field) {
                    $params = array('label' => $field) + $rule->getParams();
                    if (!is_numeric($key)) {
                        $field = $key;
                    }
                    $this->executeRule($rule, $field, $params);
                }
            } else {
                $params = array('label' => $fields) + $rule->getParams();
                $this->executeRule($rule, $fields, $params);
            }
        }
        return $this->errors;
    }

    public function __call($name, $args)
    {
        if (isset(self::$customRules[$name])) {
            $class = new \ReflectionClass(self::$customRules[$name]);
            $this->rules[] = $class->newInstanceArgs($args);
            return $this;
        } else {
            throw new \BadMethodCallException('Call to undefined method Scoop\Validator::'.$name.'()');
        }
    }

    public static function addRule($name, $class = null)
    {
        if (is_array($name)) {
            foreach ($name as $n => $class) {
                self::addRule($n, $class);
            }
            return;
        }
        if (get_parent_class($class) !== 'Scoop\Validation\Rule') {
            throw new \UnexpectedValueException($class.' class isn\'t an instance of \Scoop\Validation\Rule');
        }
        self::$customRules[$name] = $class;
    }

    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    private function executeRule($rule, $field, $params)
    {
        $name = $rule->getName();
        $value = null;
        if (isset($this->data[$field])) {
            $value = $this->data[$field];
        } elseif ($name === 'required') {
            $name = 'on';
        } else {
            return;
        }
        $params += array('value' => $value);
        if ($rule->isIncludeInputs()) {
            $this->convertInputs($params['inputs']);
        }
        if ($this->typeValidation === self::SIMPLE_VALIDATION) {
            if (!isset($this->errors[$field]) && !$rule->validate($params)) {
                $this->errors[$field] = self::formatMessage($name, $params);
            }
        } elseif (!$rule->validate($params)) {
            $this->errors[$field][] = self::formatMessage($name, $params);
        }
    }

    private function convertInputs(&$inputs)
    {
        if (is_array($inputs)) {
            foreach ($inputs as $key => $value) {
                $inputs[$value] = is_numeric($key)?
                    $this->data[$value]:
                    $this->data[$key];
                unset($inputs[$key]);
            }
        } else {
            $inputs = array(
                $inputs => $this->data[$inputs]
            );
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
