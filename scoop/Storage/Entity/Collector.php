<?php
namespace Scoop\Storage\Entity;

class Collector
{
    private $entities = array();
    private $persisted = array();
    private $prepared = array();
    private $statements = array();

    public function add($entity, $mapper)
    {
        $reflection = new \ReflectionObject($entity);
        $className = $reflection->getName();
        if (!isset($mapper['table'])) throw new \RuntimeException($className.' not mapper configured');
        $properties = $reflection->getProperties();
        $fields = array();
        foreach ($properties as $prop) {
            $name = $prop->getName();
            $prop->setAccessible(true);
            if (isset($mapper['fields'][$name])) {
                if (isset($mapper['fields'][$name]['name'])) {
                    $name = $mapper['fields'][$name]['name'];
                }
                $fields[$name] = $prop->getValue($entity);
            }
        }
        $id = isset($mapper['id']) ? $mapper['id'] : 'id';
        if (isset($fields[$id])) {
            $id = $fields[$id];
        } else {
            $id = uniqid();
            unset($fields[$id]);
        }
        $this->entities[$className.':'.$id] = $entity;
        if (isset($this->persisted[$className.':'.$id])) {
            $this->prepared[$className.':'.$id] = $this->persisted[$className.':'.$id]
            ->update($fields)
            ->restrict('id = :id');
            return;
        }
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = new \Scoop\Storage\SQO($mapper['table']);
        }
        $this->prepared[$className.':'.$id] = $this->statements[$className]->create($fields);
    }

    public function remove($entity)
    {
        $key = array_search($entity, $this->entities);
        unset($this->entities[$key]);
        if (isset($this->persisted[$key])) {
            $this->prepared[$key] = $this->persisted[$key]
            ->delete()
            ->restrict('id = :id');
        } else {
            unset($this->prepared[$key]);
        }
    }

    public function save()
    {
        foreach ($this->prepared as $key => $prepared) {
            $index = strpos($key, ':');
            if ($prepared instanceof \Scoop\Storage\SQO\Factory) {
                $prepared->run();
            } else {
                $id = substr($key, $index + 1);
                $prepared->run(compact('id'));
            }
            $this->persisted[$key] = $this->statements[substr($key, 0, $index)];
        }
    }

    public function make($className, $id, $row, $fields, $sqo)
    {
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = $sqo;
        }
        $this->persisted[$className.':'.$id] = $this->statements[$className];
        if (!isset($this->entities[$className.':'.$id])) {
            $reflectionClass = new \ReflectionClass($className);
            $constructor = $reflectionClass->getConstructor();
            $args = array_fill(0, $constructor->getNumberOfRequiredParameters(), null);
            $entity = $constructor ? $reflectionClass->newInstanceArgs($args) : $reflectionClass->newInstanceWithoutConstructor();
            $object = new \ReflectionObject($entity);
            foreach ($row as $name => $value) {
                $prop = $object->getProperty($fields[$name]);
                $prop->setAccessible(true);
                $prop->setValue($entity, $value);
            }
            $this->entities[$className.':'.$id] = $entity;
        }
        return $this->entities[$className.':'.$id];
    }
}
