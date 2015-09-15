<?php
namespace Scoop\Storage\SQO;

class Expander extends Result
{
    public function __construct($query, $type, &$connexion)
    {
        parent::__construct($query, $type, $connexion);
    }

    public function join($table, $using = null, $type = 'INNER')
    {
        $join = ', '.$table;
        if ($type === 'LEFT' || $type === 'RIGHT' || $type === 'FULL') {
            $type .= ' OUTER';
        }

        if ($using !== null) {
            $join = ' '.$type.' JOIN '.$table
                .(preg_match('/\s+([<>!=]{1,2}|NOT ?LIKE)\s+/', $using)?
                    ' ON('.$using.')':
                    ' USING('.$using.')');
        }
        $this->from[] = $join;
        return $this;
    }
}
