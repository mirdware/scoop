<?php

namespace Scoop\Validation;

abstract class Rule
{
    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Obtiene los párametros de apoyo (max, min, etc).
     * @return array<string|integer> Párametros de apoyo
     */
    public function getParams()
    {
        $params = get_object_vars($this);
        unset($params['data']);
        return $params;
    }

    /**
     * Valida si se cumple o no con la condición configurada por la clase hija
     * @param array $params Párametros enviados para la validación (apoyo + valor)
     * @return boolean Pasa o no la validación
     */
    abstract public function validate($value);
}
