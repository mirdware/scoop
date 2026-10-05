<?php

namespace Scoop\Bootstrap\Loader;

class TypeInstantiator
{
    private $context;
    private $mapper;
    private $instances;

    public function __construct(\Scoop\Bootstrap\Environment $context, TypeMapper $mapper)
    {
        $this->context = $context;
        $this->mapper = $mapper;
        $this->instances = array();
    }

    public function load($type)
    {
        if (!isset($this->instances[$type])) {
            $derivedTypes = $this->mapper->load($type);
            $instancesTypes = array();
            foreach ($derivedTypes as $derivedType) {
                $instancesTypes[] = $this->context->inject($derivedType);
            }
            $this->instances[$type] = $instancesTypes;
        }
        return $this->instances[$type];
    }
}
