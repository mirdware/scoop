<?php
namespace Scoop\Validation;

class Equals extends Rule
{
    protected $fail;
    private $hasInputs;
    private $values;
    private $inputs;

    /**
     * Validar si un campo contiene la misma información que otros campos o un valor dado:
     * ->equals('input', ['input2'])
     * ->equals('input', 'hello')
     * ->equals(['input1', 'input2'], 'hello')
     * @param string|array $fields Nombres de los campos que deben cumplir con la regla,
     * si es un string solo se validara un solo campo
     * @param string|array $inputs Nombre de los campos que deben contener información 
     * similar al campo principal, si es un solo string se validara como valor y no como
     * campo
     */
    public function __construct($fields, $inputs)
    {
        parent::__construct($fields);
        $this->hasInputs = is_array($inputs);
        if ($this->hasInputs) {
            $this->inputs = $inputs;
        } else {
            $this->fail = $inputs;
        }
    }

    /**
     * Setea los valores de $this->values con los datos suministrados a validar.
     * @param  array $inputs Nombre del campo o campos a ser convertido.
     */
    public function setValues($data)
    {
        if (!$this->hasInputs) return;
        $this->values = array();
        foreach ($this->inputs as $key => $value) {
            $value = is_numeric($key) ? $value : $key;
            $this->values[$key] = $data[$value];
        }
    }

    public function validate($value)
    {
        if (empty($value)) return true;
        if (!$this->hasInputs) return $this->fail == $value;
        foreach ($this->values as $key => $input) {
            if ($input !== $value) {
                $this->fail = $this->inputs[$key];
                return false;
            }
        }
        return true;
    }
}
