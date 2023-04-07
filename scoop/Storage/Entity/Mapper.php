<?php

namespace Scoop\Storage\Entity;

abstract class Mapper
{
    protected $map;

    public function __construct($map)
    {
        $this->map = $map;
    }

    protected function getIdName($className)
    {
        $idName = 'id';
        if (isset($this->map[$className]['id'])) {
            $idName = $this->map[$className]['id'];
            if (isset($this->map[$className]['properties'][$idName]['name'])) {
                $idName = $this->map[$className]['properties'][$idName]['name'];
            }
        }
        return $idName;
    }
}
