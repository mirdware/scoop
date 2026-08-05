<?php

namespace Scoop\Persistence\Entity\Mapper;

class Extractor
{
    private $entityMap;
    private $valueMap;
    private $typeMapper;
    private $accessor;
    private $fieldTypes;

    public function __construct($entityMap, $valueMap, $typeMapper, $accessor)
    {
        $this->entityMap = $entityMap;
        $this->valueMap = $valueMap;
        $this->typeMapper = $typeMapper;
        $this->accessor = $accessor;
        $this->fieldTypes = array();
    }

    public function getFields($entity, $className, $mapper)
    {
        $fields = array();
        $accessor = $this->accessor->get($className);
        foreach ($mapper['properties'] as $propName => $propDef) {
            $fieldName = isset($propDef['column']) ? $propDef['column'] : \Scoop\Persistence\Entity\Mapper::toColumn($propName);
            $value = $accessor($entity, $propName);
            if (isset($mapper['relations'][$propName]) && is_object($value)) {
                $relatedClassName = get_class($value);
                while ($parent = get_parent_class($relatedClassName)) {
                    $relatedClassName = $parent;
                }
                $idName = \Scoop\Persistence\Entity\Mapper::resolveIdName($this->entityMap, $relatedClassName);
                $relatedAccessor = $this->accessor->get($relatedClassName);
                $value = $relatedAccessor($value, $idName);
            }
            $type = $propDef['type'];
            if (isset($this->valueMap[$type])) {
                $valueMap = $this->valueMap[$type];
                if ($value !== null && !is_a($value, $type)) {
                    throw new \UnexpectedValueException(gettype($value) . ' not is a ' . $type);
                }
                $voAccessor = $this->accessor->get($type);
                if (count($valueMap) > 1) {
                    foreach ($valueMap as $name => $prop) {
                        $fName = $fieldName . '_' . (isset($prop['column']) ? $prop['column'] : \Scoop\Persistence\Entity\Mapper::toColumn($name));
                        $fields[$fName] = $voAccessor($value, $name);
                    }
                } else {
                    $fields[$fieldName] = $voAccessor($value, key($valueMap));
                }
            } else {
                $fields[$fieldName] = $this->typeMapper->getRowValue($type, $value);
            }
        }
        return $fields;
    }

    public function getChangedFields($className, &$persistedFields, $entityFields)
    {
        $types = $this->getFieldTypes($className);
        $fields = array();
        foreach ($persistedFields as $key => $oldValue) {
            $type = isset($types[$key]) ? $types[$key] : null;
            if (!$this->typeMapper->isSame($type, $oldValue, $entityFields[$key])) {
                $fields[$key] = $entityFields[$key];
                $persistedFields[$key] = $fields[$key];
            }
        }
        return $fields;
    }

    public function clearNulls($value, $only = null)
    {
        $filtered = array();
        foreach ($value as $key => $element) {
            if (($only !== null && !isset($only[$key])) || $element !== null) {
                $filtered[$key] = $element;
            }
        }
        return $filtered;
    }

    private function getFieldTypes($className)
    {
        if (!isset($this->fieldTypes[$className])) {
            $types = array();
            foreach ($this->entityMap[$className]['properties'] as $propName => $propDef) {
                $fieldName = isset($propDef['column']) ? $propDef['column'] : \Scoop\Persistence\Entity\Mapper::toColumn($propName);
                $type = $propDef['type'];
                if (isset($this->valueMap[$type])) {
                    $valueMap = $this->valueMap[$type];
                    if (count($valueMap) > 1) {
                        foreach ($valueMap as $voName => $voDef) {
                            $fName = $fieldName . '_' . (isset($voDef['column']) ? $voDef['column'] : \Scoop\Persistence\Entity\Mapper::toColumn($voName));
                            $types[$fName] = $voDef['type'];
                        }
                    } else {
                        $voName = key($valueMap);
                        $types[$fieldName] = $valueMap[$voName]['type'];
                    }
                } else {
                    $types[$fieldName] = $type;
                }
            }
            $this->fieldTypes[$className] = $types;
        }
        return $this->fieldTypes[$className];
    }
}
