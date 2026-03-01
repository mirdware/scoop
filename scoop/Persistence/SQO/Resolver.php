<?php

namespace Scoop\Persistence\SQO;

class Resolver
{
    private $creator;
    private $columns;
    private $action;
    private $sqo;
    private $updateFields;

    public function __construct(Creator $creator, \Scoop\Persistence\SQO $sqo, $columns, $updateFields)
    {
        $this->creator = $creator;
        $this->sqo = $sqo;
        $this->updateFields = $updateFields;
        $this->columns = $columns;
    }

    public function doUpdate($fields = null)
    {
        $this->action = 'UPDATE';
        $this->updateFields = $fields;
        return $this->creator;
    }

    public function doNothing()
    {
        $this->action = 'NOTHING';
        return $this->creator;
    }

    public function __toString()
    {
        if (!$this->action) {
            return '';
        }
        $connection = $this->sqo->getConnection();
        if ($this->action === 'NOTHING') {
            if ($connection->is('mysql')) {
                return " ON DUPLICATE KEY UPDATE {$this->columns[0]}={$this->columns[0]}";
            }
            return ' ON CONFLICT (' . implode(', ', $this->columns) .') DO NOTHING';
        }
        if ($connection->is('mysql')) {
            return ' ON DUPLICATE KEY UPDATE ' . $this->getUpdateFields(true);
        }
        return ' ON CONFLICT (' . implode(', ', $this->columns) . ') DO UPDATE SET ' . $this->getUpdateFields(false);
    }

    private function getUpdateFields($isMySQL)
    {
        $update = array();
        foreach ($this->updateFields as $field => $value) {
            if (is_numeric($field)) {
                $field = $value;
                $value = $isMySQL ? "VALUES($field)" : "EXCLUDED.$field";
            }
            if (in_array($field, $this->columns)) {
                continue;
            }
            $update[] = "$field=$value";
        }
        return implode(', ', $update);
    }
}
