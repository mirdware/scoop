<?php

namespace Scoop\Persistence\Entity;

class DiscriminatorMapper
{
    private $root;
    private $entityMap;
    private $discriminator;

    public function __construct($root, $entityMap)
    {
        $this->root = $root;
        $this->entityMap = $entityMap;
        $this->discriminator = $this->getDiscriminator();
    }

    public function discriminate(&$row)
    {
        if (
            !$this->discriminator ||
            !isset($row[$this->discriminator['column']]) ||
            !isset($this->discriminator['map'][$row[$this->discriminator['column']]])
        ) {
            return $this->root;
        }
        $root = $this->discriminator['map'][$row[$this->discriminator['column']]];
        if (!isset($this->entityMap[$root])) {
            return $root;
        }
        $idName = isset($this->entityMap[$root]['id']) ? $this->entityMap[$root]['id'] : 'id';
        $sqo = new \Scoop\Persistence\SQO($this->entityMap[$root]['table'], 'r');
        $reader = $sqo->read();
        $reader->restrict("r.$idName = :id");
        $row += $reader->run(array('id' => $row[$idName]))->fetch();
        if (isset($this->entityMap[$root]['discriminator']['map'])) {
            $discriminator = new DiscriminatorMapper($root, $this->entityMap);
            return $discriminator->discriminate($row);
        }
        return $root;
    }

    public function getColumn()
    {
        return $this->discriminator ? $this->discriminator['column'] : null;
    }

    private function getDiscriminator()
    {
        if (
            !isset($this->entityMap[$this->root]['discriminator']['map']) ||
            !isset($this->entityMap[$this->root]['discriminator']['column'])
        ) {
            return null;
        }
        $discriminator = $this->entityMap[$this->root]['discriminator'];
        $map = array();
        foreach ($discriminator['map'] as $key => $value) {
            $map[$value] = $key;
        }
        $discriminator['map'] = $map;
        return $discriminator;
    }
}
