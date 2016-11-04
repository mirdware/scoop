<?php
namespace Scoop;

/**
 * Clase encargada de validar datos segun unas reglas prestablecidas o creadas por el
 * mismo desarrollador.
 */
class Validator
{
    /**
     * Validación sencilla que permite una validación por cada regla.
     */
    const SIMPLE_VALIDATION = 0;
    /**
     * Validaci´on completa que realiza todas las validaciones necesarias.
     */
    const FULL_VALIDATION = 1;
    /**
     * Mensaje por defecto si no se hubica un mensaje personalizado para la regla.
     */
    const DEFAULT_MSG = 'Invalid field';
    /**
     * Mensajes personalizados para las reglas registradas.
     * @var array
     */
    private static $msg = array();
    /**
     * Clases de las reglas prestablecidas que maneja el bootstrap.
     * @var array
     */
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
    /**
     * Instancias de las reglas que se van a usar, estas se crean on-demand
     * @var array
     */
    private $rules = array();
    /**
     * Datos que seran validados.
     * @var array
     */
    private $data;
    /**
     * Errores presentados durante la validación de los datos.
     * @var array
     */
    private $errors;
    /**
     * Tipo de validación seleccionado SIMPLE_VALIDATION FULL_VALIDATION
     * @var integer
     */
    private $typeValidation;

    /**
     * Crea el objeto \Scoop\Validator con un tipo de validación.
     * @param integer $typeValidation SIMPLE_VALIDATION (por defecto) o FULL_VALIDATION
     */
    public function __construct($typeValidation = self::SIMPLE_VALIDATION)
    {
        $this->typeValidation = $typeValidation;
    }

    /**
     * Genera la validación de los datos, retornando los errores encontrados
     * @param  array $data Datos a ser validados ("nombreCampo" => "valor")
     * @return array       Errres hallados durante el proceso de validación
     */
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

    /**
     * Se encarga de realizar el llamado dinamico de las reglas definidas.
     * @param  string $name Nombre de la regla que se desea construir.
     * @param  array $args Argumentos pasados al constructor de la regla.
     * @return \Scoop\Validator La instancia de la clase para encadenamiento.
     */
    public function __call($name, $args)
    {
        if (!isset(self::$customRules[$name])) {
            throw new \BadMethodCallException('Call to undefined method Scoop\Validator::'.$name.'()');
        }
        $class = new \ReflectionClass(self::$customRules[$name]);
        $this->rules[] = $class->newInstanceArgs($args);
        return $this;
    }

    /**
     * Registra o modifica una regla dentro de $customRules.
     * @param string|array $name  Nombre con el que se identificara la regla o 
     * par ($name => $class).
     * @param string $class Identificador de la clase que se encargara de resolver la regla.
     */
    public static function addRule($name, $class = null)
    {
        if (is_array($name)) {
            foreach ($name as $n => $class) {
                self::addRule($n, $class);
            }
        } else {
            if (get_parent_class($class) !== 'Scoop\Validation\Rule') {
                throw new \UnexpectedValueException($class.' class isn\'t an instance of \Scoop\Validation\Rule');
            }
            self::$customRules[$name] = $class;
        }
    }

    /**
     * Establece cual sera el array de mensajes personalizados para cada regla.
     * @param array $messages par ("nombreRegla" => "mensaje").
     */
    public static function setMessages($messages)
    {
        self::$msg = (array) $messages;
    }

    /**
     * Según los datos suministrados se encarga de ejecutar las reglas pertinentes a cada uno.
     * @param  string $rule   Nombre de la regla que sera ejecutada.
     * @param  string $field  Nombre del campo que sera validado
     * @param  array $params Parametros pasados a la regla (max, min, etc).
     */
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

    /**
     * Convierte los inputs "Hermanos" que son enviados como parametros.
     * @param  string|array &$inputs Nombre del campo o campos a ser convertido.
     */
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

    /**
     * Crea el mensaje que sera mostrado en la notificación de errores.
     * @param  string $rule   Nombre de la regla de la cual se desea obtener el mesaje.
     * @param  array $params Parametros que fueron enviados a la regla (max, min, etc).
     * @return string         Mensaje formatiado para su notificación.
     */
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
