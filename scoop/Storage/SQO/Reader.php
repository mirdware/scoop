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

    public function page($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $size = isset($params['size']) ? intval($params['size']) : 12;
        unset($params['page'], $params['size']);
        $sql = $this->query;
        $this->query = preg_replace('/^SELECT (.*) FROM /', 'SELECT COUNT(*) AS total FROM ', $sql);
        $paginated = $this->run($params)->fetch(\PDO::FETCH_ASSOC);
        $this->query = $sql;
        $clone = clone $this;
        $result = $clone->limit($page * $size, $size)->run($params)->fetchAll();
        return $paginated + compact('page', 'size', 'result');
    }
}
