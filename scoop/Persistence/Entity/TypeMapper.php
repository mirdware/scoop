<?php

namespace Scoop\Persistence\Entity;

class TypeMapper
{
    private $types = array(
        'string' => 'Scoop\Persistence\Entity\Type\Varchar',
        'serial' => 'Scoop\Persistence\Entity\Type\Serial',
        'numeric' => 'Scoop\Persistence\Entity\Type\Numeric',
        'int' => 'Scoop\Persistence\Entity\Type\Integer',
        'bool' => 'Scoop\Persistence\Entity\Type\Boolean',
        'date' => 'Scoop\Persistence\Entity\Type\Date',
        'json' => 'Scoop\Persistence\Entity\Type\Json',
        'json:array' => 'Scoop\Persistence\Entity\Type\JsonArray'
    );
    private $instances = array();

    public function __construct($types)
    {
        $this->types += $types;
    }

    public function getRowValue($type, $value)
    {
        if ($value === null) return null;
        $instance = $this->getInstance($type);
        return $instance ? $instance->disassemble($value) : $value;
    }

    public function getEntityValue($type, $value)
    {
        if ($value === null) return null;
        $instance = $this->getInstance($type);
        return $instance ? $instance->assemble($value) : $value;
    }

    public function hasAutoIncrement($type)
    {
        $instance = $this->getInstance($type);
        return $instance &&
        method_exists($instance, 'isAutoincremental') &&
        $instance->isAutoincremental();
    }

    public function isSame($type, $oldValue, $newValue)
    {
        $instance = $this->getInstance($type);
        return $instance && method_exists($instance, 'comparate') ?
        $instance->comparate($oldValue, $newValue) :
        $oldValue === $newValue;
    }

    private function getInstance($type)
    {
        if (isset($this->types[$type])) {
            if (!isset($this->instances[$type])) {
                $this->instances[$type] = new $this->types[$type]();
            }
            return $this->instances[$type];
        }
        return null;
    }
}
