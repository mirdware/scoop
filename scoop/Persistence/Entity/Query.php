<?php

namespace Scoop\Persistence\Entity;

class Query
{
    private $root;
    private $joins;
    private $fields;
    private $mapper;
    private $map;
    private $aggregates;

    public function __construct($mapper, $aggregate, $map)
    {
        $this->map = $map;
        $this->root = $aggregate;
        $this->mapper = $mapper;
        $this->fields = $this->getFields($this->root, 'r', false);
        $this->joins = array();
        $this->aggregates = array();
    }

    public function aggregate($aggregates)
    {
        $aggregates = explode('.', $aggregates);
        $left = $this->root;
        $entityMap = $this->map['entities'][$left];
        $leftAlias = 'r';
        $aggregateList = &$this->aggregates;
        $prefix = '';
        foreach ($aggregates as $propertyName) {
            if (!isset($entityMap['relations'][$propertyName])) {
                throw new \UnexpectedValueException('Relation ' . $propertyName . ' not found');
            }
            if (isset($aggregateList[$propertyName])) {
                $leftAlias = $aggregateList[$propertyName]['alias'];
                $prefix = $leftAlias . '$a$';
                $aggregateList = &$aggregateList[$propertyName]['aggregates'];
                continue;
            }
            $relation = $entityMap['relations'][$propertyName];
            $aggregate = $relation[0];
            $leftId = $this->mapper->getIdName($left);
            $rightId = $this->mapper->getIdName($aggregate);
            $rightAlias = $prefix . $this->toColumn($propertyName);
            $aggregateMap = $this->map['entities'][$aggregate];
            $joinType = 'inner';
            if (isset($entityMap['properties'][$propertyName])) {
                $property = $entityMap['properties'][$propertyName];
                if (!empty($property['nullable'])) {
                    $joinType = 'left';
                }
                $columnName = isset($property['column']) ? $property['column'] : $this->toColumn($propertyName);
                $comparation = "$leftAlias.$columnName=$rightAlias.$rightId";
            } else {
                $relationName = $relation[1];
                $joinType = 'left';
                if ($relation[2] === Relation::MANY_TO_MANY) {
                    $relation = explode(':', $relationName);
                    $relation = $this->map['relations'][$relation[1]];
                    $relId = $relation['entities'][$left]['column'];
                    $comparation = "$leftAlias.$leftId=$leftAlias$rightAlias.$relId";
                    $this->joins[] = array($relation['table'] . ' r' . $rightAlias, $comparation, $joinType);
                    $relId = $relation['entities'][$aggregate]['column'];
                    $comparation = "$leftAlias$rightAlias.$relId=$rightAlias.$rightId";
                    $joinType = 'inner';
                } else {
                    $property = $aggregateMap['properties'][$relationName];
                    $columnName = isset($property['column']) ? $property['column'] : $this->toColumn($relationName);
                    $comparation = "$leftAlias.$leftId=$rightAlias.$columnName";
                }
            }
            $aggregateList[$propertyName] = array('type' => $aggregate, 'alias' => $rightAlias, 'aggregates' => array());
            $this->joins[] = array($aggregateMap['table'] . ' ' . $rightAlias, $comparation, $joinType);
            $leftAlias = $rightAlias;
            $left = $aggregate;
            $prefix = $leftAlias . '$a$';
            $aggregateList = &$aggregateList[$propertyName]['aggregates'];
            $entityMap = $this->map['entities'][$left];
            $this->fields = array_merge($this->fields, $this->getFields($aggregate, $rightAlias, false));
        }
        return $this;
    }

    public function matching($filters, $fields = null, $order = null)
    {
        $reader = $this->createReader();
        if ($filters) {
            preg_match_all('/[\s=\(]\:(\w+)/', $filters, $matches);
            $replacement = array_combine($matches[1], $matches[1]);
            foreach ($this->fields as $key => $name) {
                $key = str_replace(array('$a$', '$v$'), array('.', ':'), $key);
                $replacement[$key] = $name;
            }
            $filters = strtr($filters, $replacement);
            $reader->restrict($filters);
        }
        if ($order) {
            $order = str_replace(array('.', ':'), array('$a$', '$v$'), $order);
            $reader->order($order);
        }
        $result = $reader->run($fields);
        $idName = $this->mapper->getTableId($this->root);
        $fields = $this->getFields($this->root, 'r', true);
        $rows = $result->fetchAll();
        $aggregates = array();
        foreach ($rows as $row) {
            if (!isset($aggregates[$row[$idName]])) {
                $aggregateRoot = $this->mapper->make($this->root, $row[$idName], $row, $fields);
                $aggregates[$row[$idName]] = array('root' => $aggregateRoot, 'rows' => array());
            }
            $aggregates[$row[$idName]]['rows'][] = $row;
        }
        $aggregateRootList = array();
        foreach ($aggregates as $aggregate) {
            $this->assignAggregates($this->root, 'r', $aggregate['root'], $this->aggregates, $aggregate['rows']);
            $aggregateRootList[] = $aggregate['root'];
        }
        return $aggregateRootList;
    }

    public function get($id)
    {
        $reader = $this->createReader();
        $idName = $this->mapper->getTableId($this->root);
        $reader->restrict("r.$idName = :id");
        $result = $reader->run(compact('id'));
        $fields = $this->getFields($this->root, 'r', true);
        $rows = $result->fetchAll();
        if (!$rows) return null;
        $aggregateRoot = $this->mapper->make($this->root, $rows[0][$idName], $rows[0], $fields);
        $this->assignAggregates($this->root, 'r', $aggregateRoot, $this->aggregates, $rows);
        return $aggregateRoot;
    }

    private function assignAggregates($name, $alias, $entity, $aggregateList, $rows)
    {
        $object = new \ReflectionObject($entity);
        $entityMap = $this->map['entities'][$name];
        $idName = $this->mapper->getTableId($name);
        $prefix = $alias !== 'r' ? $alias . '$a$' : '';
        $row = $this->findRow($prefix . $idName, $object->getProperty($idName)->getValue($entity), $rows);
        foreach ($aggregateList as $name => $map) {
            $alias = $map['alias'];
            $className = $map['type'];
            $fields = $this->getFields($className, $alias, true);
            $prefix = $alias !== 'r' ? $alias . '$a$' : '';
            $idName = $prefix . $this->mapper->getTableId($className);
            $relationType = $entityMap['relations'][$name][2];
            $isArray = $relationType === Relation::ONE_TO_MANY || $relationType === Relation::MANY_TO_MANY;
            $value = array();
            $id = $row[$idName];
            if (!$id) {
                if (!$isArray) continue;
            } elseif ($isArray) {
                foreach ($rows as $r) {
                    $id = $r[$idName];
                    if (!isset($value[$id])) {
                        $value[$id] = $this->mapper->make($className, $id, $r, $fields);
                        if (!empty($map['aggregates'])) {
                            $this->assignAggregates($className, $alias, $value[$id], $map['aggregates'], $rows);
                        }
                    }
                }
                $value = array_values($value);
            } else {
                $value = $this->mapper->make($className, $id, $row, $fields);
                if (!empty($relation['aggregates'])) {
                    $this->assignAggregates($className, $alias, $value, $map['aggregates'], $rows);
                }
            }
            $prop = $object->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($entity, $value);
        }
    }

    private function findRow($idName, $id, $rows)
    {
        foreach ($rows as $row) {
            if ($row[$idName] === $id) {
                return $row;
            }
        }
    }

    private function createReader()
    {
        $sqo = new \Scoop\Persistence\SQO($this->map['entities'][$this->root]['table'], 'r');
        $reader = $sqo->read($this->fields);
        foreach ($this->joins as $join) {
            $reader->join($join[0], $join[1], $join[2]);
        }
        return $reader;
    }

    private function getFields($entity, $table, $isProp)
    {
        $fields = array();
        foreach ($this->map['entities'][$entity]['properties'] as $key => $value) {
            $key = $this->toColumn($key);
            $alias = ($table !== 'r' ? $table . '$a$' : '') . $key;
            if (isset($this->map['values'][$value['type']])) {
                if (count($this->map['values'][$value['type']]) > 1) {
                    foreach ($this->map['values'][$value['type']] as $name => $object) {
                        $name = $this->toColumn($name);
                        $columnName = isset($object['column']) ? $object['column'] : $name;
                        $fields[$alias . '$v$' . $name] = "{$table}.{$key}_{$columnName}";
                    }
                } else {
                    $fields[$alias] = "$table.$key";
                }
            } else {
                $value = isset($value['column']) ? $value['column'] : $key;
                $fields[$alias] = $isProp ? $key : "$table.$value";
            }
        }
        return array_merge($fields, $this->getParentsFields($entity, $table, $isProp));
    }

    private function getParentsFields($entity, $table, $isProp)
    {
        $ref = new \ReflectionClass($entity);
        $index = 0;
        $fields = array();
        $id = $this->mapper->gettableId($ref->getName());
        while ($parent = $ref->getParentClass()) {
            $name = $parent->getName();
            $parentAlias = 'p' . $index . '$' . $table;
            if (!$isProp) {
                $parentId = $this->mapper->getTableId($name);
                $parentTable = $this->map['entities'][$name]['table'];
                $this->joins[] = array("$parentTable $parentAlias", "$parentAlias.$parentId=$table.$id", 'inner');
            }
            $parentFields  = $this->getFields($name, $parentAlias, $isProp);
            foreach ($parentFields as $name => $parentField) {
                $name = str_replace($parentAlias, $table, $name);
                if ($isProp) {
                    $fields[$index][$name] = $parentField;
                } else {
                    $fields[$name] = $parentField;
                }
            }
            $ref = $parent;
            $index++;
        }
        return $fields;
    }

    private function toColumn($property)
    {
        return strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $property));
    }
}
