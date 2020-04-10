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
        $parameters = $this->assign(new \stdClass(), $this);
        $this->query = preg_replace('/^SELECT (.*) FROM /', 'SELECT COUNT(*) AS total FROM ', $parameters->query);
        $this->order = array();
        $this->group = array();
        $paginated = $this->run($params)->fetch(\PDO::FETCH_ASSOC);
        $this->assign($this, $parameters);
        $clone = clone $this;
        $result = $clone->limit($page * $size, $size)->run($params)->fetchAll();
        return $paginated + compact('page', 'size', 'result');
    }

    private function assign($target, $source)
    {
        $keys = array('query', 'order', 'group');
        foreach ($keys as $key) {
            $target->$key = $source->$key;
        }
        return $target;
    }
}
