<?php

namespace Scoop\Persistence\Entity\Resolver;

class Join
{
    private $mapper;
    private $map;
    private $fieldResolver;

    public function __construct($mapper, $map, $fieldResolver)
    {
        $this->mapper = $mapper;
        $this->map = $map;
        $this->fieldResolver = $fieldResolver;
    }

    public function resolve($root, $aggregatesPath, &$aggregates)
    {
        $aggregatesPath = explode('.', $aggregatesPath);
        $left = $root;
        $entityMap = $this->map['entities'][$left];
        $leftAlias = 'r';
        $aggregateList = &$aggregates;
        $prefix = '';
        $ancestorOptional = false;
        foreach ($aggregatesPath as $propertyName) {
            if (!isset($entityMap['relations'][$propertyName])) {
                throw new \UnexpectedValueException('Relation ' . $propertyName . ' not found');
            }
            $relation = $entityMap['relations'][$propertyName];
            if (isset($aggregateList[$propertyName])) {
                $leftAlias = $aggregateList[$propertyName]['alias'];
                $prefix = $leftAlias . '$a$';
                $aggregateList = &$aggregateList[$propertyName]['aggregates'];
                $ancestorOptional = $ancestorOptional || $this->isOptional($entityMap, $propertyName);
                $entityMap = $this->map['entities'][$relation[0]];
                $left = $relation[0];
                continue;
            }
            $aggregate = $relation[0];
            $leftId = $this->mapper->getIdName($left);
            $rightId = $this->mapper->getIdName($aggregate);
            $rightAlias = $prefix . $this->mapper->toColumn($propertyName);
            $aggregateMap = $this->map['entities'][$aggregate];
            $isOptional = $ancestorOptional || $this->isOptional($entityMap, $propertyName);
            $joinType = $isOptional ? 'left' : 'inner';
            if (isset($entityMap['properties'][$propertyName])) {
                $property = $entityMap['properties'][$propertyName];
                $columnName = isset($property['column']) ? $property['column'] : $this->mapper->toColumn($propertyName);
                $comparation = "$leftAlias.$columnName=$rightAlias.$rightId";
            } else {
                $relationName = $relation[1];
                if ($relation[2] === \Scoop\Persistence\Entity\Relation::MANY_TO_MANY) {
                    $relation = explode(':', $relationName);
                    $relation = $this->map['relations'][$relation[1]];
                    $relId = $relation['entities'][$left]['column'];
                    $comparation = "$leftAlias.$leftId=$leftAlias$rightAlias.$relId";
                    $this->fieldResolver->addJoin($relation['table'] . ' ' . $leftAlias . $rightAlias, $comparation, 'left');
                    $relId = $relation['entities'][$aggregate]['column'];
                    $comparation = "$leftAlias$rightAlias.$relId=$rightAlias.$rightId";
                } else {
                    $property = $aggregateMap['properties'][$relationName];
                    $columnName = isset($property['column']) ? $property['column'] : $this->mapper->toColumn($relationName);
                    $comparation = "$leftAlias.$leftId=$rightAlias.$columnName";
                }
            }
            $aggregateList[$propertyName] = array('type' => $aggregate, 'alias' => $rightAlias, 'aggregates' => array());
            $this->fieldResolver->addJoin($aggregateMap['table'] . ' ' . $rightAlias, $comparation, $joinType);
            $leftAlias = $rightAlias;
            $left = $aggregate;
            $prefix = $leftAlias . '$a$';
            $aggregateList = &$aggregateList[$propertyName]['aggregates'];
            $entityMap = $this->map['entities'][$left];
            $this->fieldResolver->addFields($aggregate, $rightAlias, $isOptional);
            $ancestorOptional = $isOptional;
        }
    }

    private function isOptional($entityMap, $propertyName)
    {
        if (isset($entityMap['properties'][$propertyName])) {
            return !empty($entityMap['properties'][$propertyName]['nullable']);
        }
        return true;
    }
}
