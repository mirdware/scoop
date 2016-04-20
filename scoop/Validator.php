<?php
namespace Scoop;

class Validator
{
    const SIMPLE_VALIDATION = 0;
    const FULL_VALIDATION = 1;
    const DEFAULT_MSG = 'Invalid field';
    private static $msg = array();
    private static $classes = array(
        'required' => '\Scoop\Validation\Required',
        'length' => '\Scoop\Validation\Length',
        'email' => '\Scoop\Validation\Email',
        'max' => '\Scoop\Validation\Max',
        'maxLength' => '\Scoop\Validation\MaxLength',
        'min' => '\Scoop\Validation\Min',
        'minLength' => '\Scoop\Validation\MinLength',
        'number' => '\Scoop\Validation\Number',
        'pattern' => '\Scoop\Validation\Pattern',
        'range' => '\Scoop\Validation\range'
    );
    private $rules = array();
    protected $data;
    private $typeValidation;

    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->typeValidation = $typeValidation;
    }

    public function __call($name, $args)
    {
        echo $name;
        if (isset(self::$classes[$name])) {
            $class = new \ReflectionClass(self::$classes[$name]);
            $this->rules[] = $class->newInstanceArgs($args);
            return $this;
        }
    }

    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    public static function addRule($name, $class = null)
    {
        if (is_array($name)) {
            self::$classes += $name;
            return;
        }
        self::$classes[$name] = $class;
    }

    public function validate($data)
    {
        $this->data = &$data;
        $errors = array();
        foreach ($this->rules as &$rule) {
            $fields = $rule->getFields();
            if (is_array($fields)) {
                foreach ($fields as $key => &$field) {
                    $params = array('label' => $field) + $rule->getParams();
                    if (!is_numeric($key)) {
                        $field = $key;
                    }
                    $this->executeRule($rule, $field, $params, $errors);
                }
            } else {
                $params = array('label' => $fields) + $rule->getParams();
                $this->executeRule($rule, $fields, $params, $errors);
            }
        }
        return $errors;
    }

    private function executeRule($rule, $field, $params, &$errors)
    {
        if (!isset($this->data[$field])){
            return;
        }
        $params = array('value' => $this->data[$field]) + $params;

        if ($this->typeValidation === self::SIMPLE_VALIDATION) {
            if (!isset($errors[$field]) && !$rule->validate($params)) {
                $errors[$field] = self::formatMessage($rule->getName(), $params);
            }
        } elseif (!$rule->validate($params)) {
            $errors[$field][] = self::formatMessage($rule->getName(), $params);
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
