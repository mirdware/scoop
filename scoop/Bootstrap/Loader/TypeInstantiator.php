<?php

namespace Scoop\Bootstrap\Loader;

class TypeInstantiator
{
    private $mapper;
    private $instances;

    public function __construct(TypeMapper $mapper)
    {
        $this->mapper = $mapper;
        $this->instances = array();
    }

    public function load($type)
    {
        if (!isset($this->instances[$type])) {
            $derivedTypes = $this->mapper->load($type);
            $instancesTypes = array();
            foreach ($derivedTypes as $derivedType) {
                $instancesTypes[] = \Scoop\Context::inject($derivedType);
            }
            $this->instances[$type] = $instancesTypes;
        }
        return $this->instances[$type];
    }
}
