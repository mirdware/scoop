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
        if (!isset($params['page']) || !isset($params['size'])) {
            throw new \InvalidArgumentException('Parameters page and size must be supplied');
        }
        $page = $params['page'];
        $size = $params['size'];
        unset($params['page'], $params['size']);
        if (!is_numeric($page) || !is_numeric($size)) {
            throw new \InvalidArgumentException('Parameters page('.$page.') and size('.$size.') must be numerics');
        }
        $sql = $this->query;
        $this->query = preg_replace('/^SELECT (.*) FROM /', 'SELECT COUNT(*) AS total FROM ', $sql);
        $paginated = $this->run($params)->fetch(\PDO::FETCH_ASSOC);
        $this->query = $sql;
        $clone = clone $this;
        $result = $clone->limit($page * $size, $size)->run($params)->fetchAll();
        return $paginated + compact('page', 'size', 'result');
    }
}
