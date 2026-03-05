<?php

namespace Scoop\Persistence\Builder;

class Resolver
{
    private $creator;
    private $columns;
    private $action;
    private $connection;
    private $updateFields;

    public function __construct(Creator $creator, \Scoop\Persistence\Connection $connection, $columns, $updateFields)
    {
        $this->creator = $creator;
        $this->connection = $connection;
        $this->updateFields = $updateFields;
        $this->columns = array_map(array($connection, 'quoteColumn'), $columns);
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
        $isMySQL = $this->connection->is('mysql');
        if ($this->action === 'NOTHING') {
            if ($isMySQL) {
                return " ON DUPLICATE KEY UPDATE {$this->columns[0]}={$this->columns[0]}";
            }
            return ' ON CONFLICT (' . implode(', ', $this->columns) .') DO NOTHING';
        }
        if ($isMySQL) {
            return ' ON DUPLICATE KEY UPDATE ' . $this->getUpdateFields($isMySQL);
        }
        return ' ON CONFLICT (' . implode(', ', $this->columns) . ') DO UPDATE SET ' . $this->getUpdateFields(false);
    }

    private function getUpdateFields($isMySQL)
    {
        $update = array();
        foreach ($this->updateFields as $field => $value) {
            if (is_numeric($field)) {
                $field = $this->connection->quoteColumn($value);
                $value = $isMySQL ? "VALUES($field)" : "EXCLUDED.$field";
            } else {
                $field = $this->connection->quoteColumn($field);
            }
            if (in_array($field, $this->columns)) {
                continue;
            }
            $update[] = "$field=$value";
        }
        return implode(', ', $update);
    }
}
