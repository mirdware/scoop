<?php

namespace Scoop\Validation;

abstract class Rule
{
    private $validator;
    private $data;
    private $fields;

    public function attach($data, $fields)
    {
        $this->data = $data;
        $this->fields = $fields;
        return !isset($this->validator) || $this->validator->validate($data);
    }

    public function when(\Scoop\Validator $happens)
    {
        $this->validator = $happens;
        return $this;
    }

    public function unwrap()
    {
        return null;
    }

    /**
     * Obtiene los párametros de apoyo (max, min, etc).
     * @return array<string|integer> Párametros de apoyo
     */
    public function getParams()
    {
        $params = get_object_vars($this);
        unset($params['data'], $params['fields'], $params['validator']);
        return $params;
    }

    protected function get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    protected function translate($field)
    {
        return isset($this->fields[$field]) ? $this->fields[$field] : $field;
    }

    /**
     * Valida si se cumple o no con la condición configurada por la clase hija
     * @param array $params Párametros enviados para la validación (apoyo + valor)
     * @return boolean Pasa o no la validación
     */
    abstract public function validate($value);
}
