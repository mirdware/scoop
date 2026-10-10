<?php

namespace Scoop\Bootstrap\Loader;

class TypeInstantiator
{
    private $injector;
    private $mapper;
    private $instances;

    public function __construct(\Scoop\Context $context, TypeMapper $mapper)
    {
        $this->injector = $context->getInjector();
        $this->mapper = $mapper;
        $this->instances = array();
    }

    public function load($type)
    {
        if (!isset($this->instances[$type])) {
            $derivedTypes = $this->mapper->load($type);
            $instancesTypes = array();
            foreach ($derivedTypes as $derivedType) {
                $instancesTypes[] = $this->injector->get($derivedType);
            }
            $this->instances[$type] = $instancesTypes;
        }
        return $this->instances[$type];
    }
}
