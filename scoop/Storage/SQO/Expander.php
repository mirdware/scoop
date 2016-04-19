<?php
namespace Scoop\Storage\SQO;

class Expander extends Filter
{
    public function __construct($query, $type, $params, $connexion)
    {
        parent::__construct($query, $type, $params, $connexion);
    }

    public function join($table, $using = null, $type = 'INNER')
    {
        $join = ', '.$table;
        if ($using !== null) {
            if ($type === 'LEFT' || $type === 'RIGHT' || $type === 'FULL') {
                $type .= ' OUTER';
            }
            $join = ' '.$type.' JOIN '.$table
                .(preg_match('/\s+([<>!=]{1,2}|NOT ?LIKE)\s+/', $using)?
                    ' ON('.$using.')':
                    ' USING('.$using.')');
        }
        $this->from[] = $join;
        return $this;
    }
}
