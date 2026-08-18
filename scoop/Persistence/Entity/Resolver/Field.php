<?php

namespace Scoop\Persistence\Entity\Resolver;

class Field
{
    private $map;
    private $mapper;
    private $fields;
    private $joins;

    public function __construct($map, $mapper, $fields, $joins)
    {
        $this->map = $map;
        $this->mapper = $mapper;
        $this->fields = $fields;
        $this->joins = $joins;
    }

    public function addFields($entity, $table, $isOptional)
    {
        $this->fields = array_merge($this->fields, $this->resolve($entity, $table, false, $isOptional));
    }

    public function fieldsFor($entity, $table)
    {
        return $this->resolve($entity, $table, true, false);
    }

    public function addRawField($key, $value)
    {
        $this->fields[$key] = $value;
    }

    public function addJoin($table, $comparation, $joinType)
    {
        $this->joins[] = array($table, $comparation, $joinType);
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getJoins()
    {
        return $this->joins;
    }

    private function resolve($entity, $table, $isProp, $isOptional)
    {
        $fields = array();
        foreach ($this->map['entities'][$entity]['properties'] as $key => $value) {
            $key = $this->mapper->toColumn($key);
            $alias = ($table !== 'r' ? $table . '$a$' : '') . $key;
            if (isset($this->map['values'][$value['type']])) {
                if (count($this->map['values'][$value['type']]) > 1) {
                    foreach ($this->map['values'][$value['type']] as $name => $object) {
                        $name = $this->mapper->toColumn($name);
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
        return array_merge($fields, $this->parentsFields($entity, $table, $isProp, $isOptional));
    }

    private function parentsFields($className, $table, $isProp, $isOptional)
    {
        $index = 0;
        $fields = array();
        $id = $this->mapper->getTableId($className);
        $ownPrefix = $table !== 'r' ? $table . '$a$' : '';
        while ($parentName = get_parent_class($className)) {
            $parentAlias = 'p' . $index . '$' . $table;
            if (!$isProp) {
                $parentId = $this->mapper->getTableId($parentName);
                $parentTable = $this->map['entities'][$parentName]['table'];
                $joinType = $isOptional ? 'left' : 'inner';
                $this->joins[] = array("$parentTable $parentAlias", "$parentAlias.$parentId=$table.$id", $joinType);
            }
            $parentFields = $this->resolve($parentName, $parentAlias, $isProp, $isOptional);
            foreach ($parentFields as $name => $parentField) {
                $name = str_replace($parentAlias . '$a$', $ownPrefix, $name);
                if ($isProp) {
                    $fields[$index][$name] = $parentField;
                } else {
                    $fields[$name] = $parentField;
                }
            }
            $className = $parentName;
            $index++;
        }
        return $fields;
    }
}
