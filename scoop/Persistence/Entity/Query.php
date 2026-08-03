<?php

namespace Scoop\Persistence\Entity;

class Query
{
    private $root;
    private $mapper;
    private $map;
    private $aggregates;
    private $discriminator;
    private $fieldResolver;
    private $joinResolver;
    private $assembler;

    public function __construct($mapper, $aggregate, $map, $accessor, $relations)
    {
        $this->map = $map;
        $this->root = $aggregate;
        $this->mapper = $mapper;
        $this->aggregates = array();
        $this->fieldResolver = new Resolver\Field($map, $mapper);
        $this->fieldResolver->addFields($this->root, 'r', false);
        $this->joinResolver = new Resolver\Join($mapper, $map, $this->fieldResolver);
        $this->assembler = new Assembler($map, $mapper, $accessor, $this->fieldResolver, $relations);
        $this->discriminator = new Mapper\Discriminator($aggregate, $map['entities']);
        $discriminatorColumn = $this->discriminator->getColumn();
        if ($discriminatorColumn) {
            $this->fieldResolver->addRawField($discriminatorColumn, 'r.' . $discriminatorColumn);
        }
    }

    public function aggregate($aggregates)
    {
        $this->joinResolver->resolve($this->root, $aggregates, $this->aggregates);
        return $this;
    }

    public function matching($filters, $fields = null, $order = null)
    {
        $reader = $this->createReader();
        if ($filters) {
            preg_match_all('/[\s=\(]\:(\w+)/', $filters, $matches);
            $replacement = array_combine($matches[1], $matches[1]);
            foreach ($this->fieldResolver->getFields() as $key => $name) {
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
        $rows = $result->fetchAll();
        $aggregates = array();
        foreach ($rows as $row) {
            if (!isset($aggregates[$row[$idName]])) {
                $root = $this->discriminator->discriminate($row);
                $fields = $this->fieldResolver->fieldsFor($root, 'r');
                $aggregateRoot = $this->mapper->make($root, $row[$idName], $row, $fields);
                $aggregates[$row[$idName]] = array('root' => $aggregateRoot, 'rows' => array());
            }
            $aggregates[$row[$idName]]['rows'][] = $row;
        }
        $aggregateRootList = array();
        foreach ($aggregates as $aggregate) {
            $this->assembler->assign($this->root, 'r', $aggregate['root'], $this->aggregates, $aggregate['rows']);
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
        $rows = $result->fetchAll();
        if (!$rows) return null;
        $root = $this->discriminator->discriminate($rows[0]);
        $fields = $this->fieldResolver->fieldsFor($root, 'r');
        $aggregateRoot = $this->mapper->make($root, $rows[0][$idName], $rows[0], $fields);
        $this->assembler->assign($this->root, 'r', $aggregateRoot, $this->aggregates, $rows);
        return $aggregateRoot;
    }

    private function createReader()
    {
        $sqo = new \Scoop\Persistence\SQO($this->map['entities'][$this->root]['table'], 'r');
        $reader = $sqo->read($this->fieldResolver->getFields());
        foreach ($this->fieldResolver->getJoins() as $join) {
            $reader->join($join[0], $join[1], $join[2]);
        }
        return $reader;
    }
}
