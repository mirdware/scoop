<?php
namespace Scoop\Validation;

/**
 * Clase base para establecer reglas de validación
 */
abstract class Rule
{
    /**
     * Nombre de la regla que se esta validando.
     * @var string
     */
    private $name;
    /**
     * Campos que seran validados.
     * @var array
     */
    private $fields;
    /**
     * Párametros de apoyo para la validación (max, min, etc).
     * @var array
     */
    private $params;
    /**
     * Establece si la regla posee inputs "hermanos" o no.
     * @var [type]
     */
    private $includeInputs;

    /**
     * Genera la estructura necesaria para la regla
     * @param string  $name          Nombre de la regla
     * @param array  $fields        Campos a ser validados
     * @param array   $params        Párametros de apoyo
     * @param boolean $includeInputs Posee inputs "hermanos"
     */
    public function __construct($name, $fields, $params = array(), $includeInputs = false)
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->params = $params;
        $this->includeInputs = $includeInputs;
    }

    /**
     * Obtiene los campos que estan siendo validados
     * @return array Campos validados
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Obtiene los párametros de apoyo (max, min, etc)
     * @return array Párametros de apoyo
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Obtiene el nombre de la regla
     * @return string Nombre de la regla
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Determina si existen o no inputs "hermanos"
     * @return boolean existen o no inputs "hermanos"
     */
    public function isIncludeInputs()
    {
        return $this->includeInputs;
    }

    /**
     * Valida si se cumple o no con la condición configurada por la clase
     * hija
     * @param  array &$params Párametros enviados para la validación (apoyo+valor)
     * @return boolean          Pasa o no la validación
     */
    abstract public function validate(&$params);
}
