<?php
namespace Scoop\Storage\SQO;

class Reader extends Filter
{
    public function __construct($query, $type, $params, $connection)
    {
        parent::__construct($query, $type, $params, $connection);
    }

    public function join($table, $using = null, $type = 'INNER')
    {
        $join = ' NATURAL JOIN '.$table;
        if ($using !== null) {
            $type = strtoupper($type);
            if ($type !== 'INNER') {
                $type .= ' OUTER';
            }
            $join = ' '.$type.' JOIN '.$table.(
                preg_match('/\s*([<>!=]{1,2}|NOT ?LIKE)\s*/', $using) ?
                    ' ON('.$using.')' :
                    ' USING('.$using.')'
            );
        }
        $this->from[] = $join;
        return $this;
    }
}
