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

    /**
     * Crea el objeto \Scoop\Validator con un tipo de validación.
     * @param integer $typeValidation Los dos tipo de validación permitidos son
     *  SIMPLE_VALIDATION(por defecto): arroja solo un error por campo
     *  FULL_VALIDATION: arroja todos los errores que pueda tener un campo.
     */
    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->typeValidation = $typeValidation;
    }

    /**
     * Genera la validación de los datos, retornando los errores encontrados
     * @param  array<mixed> $data Datos a ser validados ("nombreCampo" => "valor")
     * @return boolean Paso o no validación.
     *  Dependiendo si es una validación simple o completa arroja un array
     *  unidimencional o multidimencional.
     */
    public function validate($data)
    {
        $this->errors = array();
        $this->data = array();
        foreach ($this->rules as $field => $rules) {
            $value = isset($data[$field]) ? $data[$field] : null;
            foreach ($rules as $rule) {
                $params = array('label' => $field);
                $rule->setData($data);
                $this->executeRule($rule, $field, $params, $value);
            }
            $this->data[$field] = $value;
        }
        return empty($this->errors);
    }

    public function add()
    {
        $args = func_get_args();
        $field = array_shift($args);
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
        if (isset($this->rules[$field])) {
            $this->rules[$field] += $args;
        } else {
            $this->rules[$field] = $args;
        }
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


    /**
     * Establece cual sera el array de mensajes personalizados para cada regla.
     * @param array<array<string>> $messages par ("nombreRegla" => "mensaje").
     */
    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    /**
     * Según los datos suministrados se encarga de ejecutar las reglas pertinentes a cada uno.
     * @param  mixed $rule   Nombre de la regla que sera ejecutada.
     * @param  string $field  Nombre del campo que sera validado
     * @param  array<mixed> $params Parametros pasados a la regla (max, min, etc).
     * @param  array<mixed> $data Datos a validar
     */
    private function executeRule($rule, $field, $params, $value)
    {
        if ($this->typeValidation === self::SIMPLE_VALIDATION) {
            if (!isset($this->errors[$field]) && !$rule->validate($value)) {
                $this->errors[$field] = $this->getMessage($rule, $params, $value);
            }
        } elseif (!$rule->validate($value)) {
            $this->errors[$field][] = $this->getMessage($rule, $params, $value);
        }
    }

    /**
     * Obtiene el mensaje formateado dependiendo del tipo enviado.
     * @param \scoop\Validation\Rule $rule Regla que esta siendo ejecutada.
     * @param array<mixed> $params Parametros a ser mostrados en el mensaje.
     * @param string $value Valor del campo.
     * @return string Mesaje formateado.
     */
    private function getMessage($rule, $params, $value)
    {
        $params += array('value' => $value) + $rule->getParams();
        $name = get_class($rule);
        if (isset(self::$msg[$name])) {
            $keys = array_keys($params);
            foreach ($keys as &$key) {
                $key = '{' . $key . '}';
            }
            return str_replace($keys, $params, self::$msg[$name]);
        }
        return self::DEFAULT_MSG;
    }
}
