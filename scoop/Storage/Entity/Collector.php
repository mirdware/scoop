<?php
namespace Scoop\Storage\Entity;

class Collector
{
    private $map;
    private $entities = array();
    private $persisted = array();
    private $removed = array();
    private $statements = array();

    public function __construct($map)
    {
        $this->map = $map;
    }

    public function add($entity)
    {
        $key = array_search($entity, $this->entities);
        if (!$key) {
            $key = $this->getKey($entity);
            $this->entities[$key];
        }
        unset($this->removed[$key]);
    }

    public function remove($entity)
    {
        $key = array_search($entity, $this->entities);
        unset($this->entities[$key]);
        if (isset($this->persisted[$key])) {
            $this->removed[$key] = $this->persisted[$key];
        }
    }

    public function save()
    {
        foreach ($this->entities as $key => $entity) {
            $object = new \ReflectionObject($entity);
            $className = $object->getName();
            $fields = $this->getFields($key, $object->getProperties());
            $this->execute($key, $fields);
            $key = $this->updateKey($key, $this->statements[$className]);
            $this->persisted[$key] = $this->statements[$className];
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

    private function getFields($key, $properties)
    {
        $entity = $this->entities[$key];
        $className = substr($key, 0, strpos($key, ':'));
        $mapper = $this->map[$className];
        $fields = array();
        foreach ($properties as $prop) {
            $name = $prop->getName();
            if (!isset($mapper['properties'][$name])) continue;
            $prop->setAccessible(true);
            $value = $prop->getValue($entity);
            if (!isset($value)) continue;
            if (isset($mapper['relations'][$name])) {
                $relation = $mapper['relations'][$name];
                $idName = isset($this->map[$relation[0]]['id']) ? $this->map[$relation[0]]['id'] : 'id';
                $object = new \ReflectionObject($value);
                $relationProp = $object->getProperty($idName);
                $relationProp->setAccessible(true);
                $value = $relationProp->getValue($value);
            }
            if (isset($mapper['properties'][$name]['name'])) {
                $name = $mapper['properties'][$name]['name'];
            }
            $fields[$name] = $value;
        }
        return $fields;
    }

    private function execute($key, $fields)
    {
        $index = strpos($key, ':');
        $className = substr($key, 0, $index);
        if (!isset($this->map[$className]['table'])) throw new \RuntimeException($className.' not mapper configured');
        if (isset($this->persisted[$key])) {
            $statement = $this->persisted[$key];
            $params = array('id' => substr($key, $index + 1));
            if (isset($this->removed[$key])) {
                return $statement->delete()->restrict('id = :id')->run($params);
            }
            return $statement->update($fields)->restrict('id = :id')->run($params);
        }
        if (!isset($this->statements[$className])) {
            $this->statements[$className] = new \Scoop\Storage\SQO($this->map[$className]['table']);
        }
        return $this->statements[$className]->create($fields)->run();
    }

    private function getKey($entity)
    {
        $object = new \ReflectionObject($entity);
        $className = $object->getName();
        $id = isset($this->map[$className]['id']) ? $this->map[$className]['id'] : 'id';
        $property = $object->getProperty($id);
        $property->setAccessible(true);
        $id = $property->getValue($entity);
        if ($id) {
            return $className.':'.$id;
        }
        $key = array_search($entity, $this->entities);
        if (!$key) {
            $key = $className.':'.uniqid();
            $this->entities[$key] = $entity;
        }
        return $key;
    }

    private function updateKey($key, $statement)
    {
        $className = substr($key, 0, strpos($key, ':'));
        $idName = isset($this->map[$className]['id']) ? $this->map[$className]['id'] : 'id';
        if (isset($this->map[$className]['properties'][$idName]['type'])) {
            $type = explode(':', $this->map[$className]['properties'][$idName]['type']);
            $isAuto = array_pop($type) === 'SERIAL';
            if ($isAuto) {
                $id = $statement->getLastId();
                $entity = $this->entities[$key];
                $object = new \ReflectionObject($entity);
                $prop = $object->getProperty($idName);
                $prop->setAccessible(true);
                $prop->setValue($entity, $id);
                $key = $className.':'.$id;
            }
        }
        return $key;
    }
}
