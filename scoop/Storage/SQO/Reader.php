<?php
namespace Scoop\Storage\SQO;

class Reader extends Filter
{
    public function __construct($query, $type, $params, $connection)
    {
        parent::__construct($query, $type, $params, $connection);
    }

    public function join($table, $using = 'NATURAL', $type = 'INNER')
    {
        $simpleType = strtoupper($using);
        if ($simpleType === 'CROSS' || $simpleType === 'NATURAL') {
            $this->from[] = ' '.$simpleType.' JOIN '.$table;
            return $this;
        }
        $type = strtoupper($type);
        if ($type !== 'INNER') {
            $type .= ' OUTER';
        }
        $this->from[] = ' '.$type.' JOIN '.$table.(
            preg_match('/\s*([<>!=]{1,2}|NOT ?LIKE)\s*/', $using) ?
                ' ON('.$using.')' :
                ' USING('.$using.')'
        );
        return $this;
    }

    public function page($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $size = isset($params['size']) ? intval($params['size']) : 12;
        unset($params['page'], $params['size']);
        $sql = 'SELECT COUNT(*) AS total FROM ('.$this->bind($params).') d';
        $paginated = $this->con->prepare($sql);
        $paginated->execute($this->getParamsAllowed($sql));
        $clone = clone $this;
        $result = $clone->limit($page * $size, $size)->run()->fetchAll();
        return $paginated->fetch(\PDO::FETCH_ASSOC) + compact('page', 'size', 'result');
    }
}
