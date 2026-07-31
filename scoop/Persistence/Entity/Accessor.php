<?php

namespace Scoop\Persistence\Entity;

class Accessor
{
    private $accessors;
    private $properties;

    public function __construct()
    {
        $this->accessors = array();
        $this->properties = array();
    }

    public function get($className)
    {
        if (!isset($this->accessors[$className])) {
            $this->accessors[$className] = \Closure::bind(function($entity, $name, $value = null) {
                if (func_num_args() === 2) {
                    return isset($entity->$name) ? $entity->$name : null;
                }
                $entity->$name = $value;
            }, null, $className);
        }
        return $this->accessors[$className];
    }

    public function getDeclaringClass($className, $property)
    {
        $key = "$className:$property";
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
        $this->properties[$key] = false;
        while ($className) {
            if (property_exists($className, $property)) {
                $this->properties[$key] = $className;
                break;
            }
            $className = get_parent_class($className);
        }
        return $this->properties[$key];
    }
}
