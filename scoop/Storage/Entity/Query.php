<?php
namespace Scoop\Storage\Entity;

class Query
{
    private $clasEntity;
    private $map;

    public function __construct($clasEntity, $map)
    {
        $this->clasEntity = $clasEntity;
        $this->map = $map;
    }
}