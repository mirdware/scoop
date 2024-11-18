<?php

namespace Scoop;

class Validator
{
    const SIMPLE_VALIDATION = 0;
    const FULL_VALIDATION = 1;
    const DEFAULT_MSG = 'Invalid field';
    private $typeValidation;
    private $errors;
    private $data;
    private $rules = array();
    private static $msg = array();

    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->typeValidation = $typeValidation;
    }

    public function validate($data)
    {
        $this->errors = array();
        $this->data = array();
        $this->setData($this->rules, $data);
        return empty($this->errors);
    }

    public function add()
    {
        $args = func_get_args();
        $field = array_shift($args);
        $this->validateArgs($field, $args);
        $this->setRules($field, $args);
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    private function executeRule($rule, $params, $value)
    {
        $field = $params['@name'];
        if ($this->typeValidation === self::SIMPLE_VALIDATION) {
            if (!isset($this->errors[$field]) && !$rule->validate($value)) {
                $this->errors[$field] = $this->getMessage($rule, $params, $value);
            }
        } elseif (!$rule->validate($value)) {
            $this->errors[$field][] = $this->getMessage($rule, $params, $value);
        }
    }

    private function getMessage($rule, $params, $value)
    {
        $params += array('@value' => is_string($value) ? $value : json_encode($value)) + $rule->getParams();
        $template = $this->getTemplateMessage($rule, self::$msg);
        if ($template) {
            $keys = array_keys($params);
            foreach ($keys as &$key) {
                $key = '{' . $key . '}';
            }
            return str_replace($keys, $params, $template);
        }
        return self::DEFAULT_MSG;
    }

    private function getTemplateMessage($rule, $messages)
    {
        $className = get_class($rule);
        if (isset($messages[$className])) {
            $unwrapped = $rule->unwrap();
            if ($unwrapped) {
                return $this->getTemplateMessage($unwrapped, $messages[$className]);
            }
            return $messages[$className];
        }
        return null;
    }

    private function getValue($field, $value) {
        $fields = explode('.', $field);
        foreach ($fields as $field) {
            if (!isset($value[$field])) {
                return null;
            }
            $value = $value[$field];
        }
        return $value;
    }

    private function setValue($field, $value) {
        $fields = explode('.', $field);
        $response = &$this->data;
        $key = array_shift($fields);
        while(count($fields)) {
            if (!isset($response[$key])) {
                $response[$key] = array();
            }
            $response = &$response[$key];
            $key = array_shift($fields);
        }
        $response[$key] = $value;
    }

    private function setRules($field, $validations)
    {
        $fields = explode('.*.', $field);
        $response = &$this->rules;
        $key = array_shift($fields);
        while(count($fields)) {
            $completeKey = $key . '.*.';
            if (!isset($response[$completeKey])) {
                $response[$completeKey] = array();
            }
            $response = &$response[$completeKey];
            $key = array_shift($fields);
        }
        if (isset($response[$key])) {
            $response[$key] += $validations;
        } else {
            $response[$key] = $validations;
        }
    }

    private function setData(&$rules, $data, $index = '')
    {
        foreach ($rules as $field => $validations) {
            $field = $index . $field;
            if (strrpos($field, '.*.')) {
                $field = substr($field, 0, -3);
                $value = $this->getValue($field, $data);
                foreach ($value as $i => $value) {
                    $this->setData($validations, $data, $field . '.' . $i . '.');
                }
            } else {
                $value = $this->getValue($field, $data);
                foreach ($validations as $validation) {
                    $params = array('@name' => $field);
                    if ($validation->with($data)) {
                        $this->executeRule($validation, $params, $value);
                    }
                }
                $this->setValue($field, $value);
            }
        }
    }

    private function validateArgs($field, $args)
    {
        if (!$field) {
            throw new \InvalidArgumentException('no field has been sent to validate');
        }
        if (!is_string($field)) {
            throw new \InvalidArgumentException('Field must be a string');
        }
        foreach ($args as $index => $validation) {
            if (!($validation instanceof \Scoop\Validation\Rule)) {
                throw new \InvalidArgumentException('Parameter ' . ($index + 2) . ' not is a Validation');
            }
        }
    }
}
