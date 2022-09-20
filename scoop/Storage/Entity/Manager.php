<?php
namespace Scoop\Storage\Entity;

class Manager
{
    private $map;
    private $collector;

    public function __construct($map)
    {
        $this->map = $map;
        $this->collector = new Collector();
    }

    public function persist($entity)
    {
        $mapper = $this->getMapper(get_class($entity));
        $this->collector->add($entity, $mapper);
        if (!isset($mapper['relations'])) return;
        $reflection = new \ReflectionObject($entity);
        foreach ($mapper['relations'] as $name => $type) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $relationEntity = $property->getValue($entity);
            if (!$relationEntity) continue;
            if (is_array($relationEntity)) {
                foreach ($relationEntity as $e) {
                    $relationMapper = $this->getMapper(get_class($e));
                    $this->collector->add($e, $relationMapper);
                }
            } else {
                $relationMapper = $this->getMapper($property->getType());
                $this->collector->add($relationEntity, $relationMapper);
            }
        }
    }

    public function remove($entity)
    {
        $this->getMapper(get_class($entity));
        $this->collector->remove($entity);
    }

    public function find($classEntity)
    {
        $this->getMapper($classEntity);
        return new Query($this->collector, $classEntity, $this->map);
    }

    public function flush()
    {
        $this->collector->save();
    }

    public function clean()
    {
        $this->collector = new Collector();
    }

    private function getMapper($classEntity)
    {
        if (!isset($this->map[$classEntity])) {
            throw new \InvalidArgumentException($classEntity.' not mapper configured');
        }
        return $this->map[$classEntity];
    }
}
