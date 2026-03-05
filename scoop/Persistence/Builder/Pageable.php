<?php

namespace Scoop\Persistence\Builder;

trait Pageable
{
    private $orderType = ' ASC';
    private $order = array();
    private $limit;
    private $offset;

    public function order()
    {
        $numArgs = func_num_args();
        if (!$numArgs) {
            throw new \InvalidArgumentException('Unsoported number of arguments');
        }
        $args = func_get_args();
        $type = strtoupper($args[$numArgs - 1]);
        if ($type === 'ASC' || $type === 'DESC') {
            $this->orderType = ' ' . $type;
            array_pop($args);
        }
        $this->order += array_map('trim', $args);
        return $this;
    }

    public function limit($offset, $limit = null)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    public function page($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 0;
        $size = isset($params['size']) ?
        intval($params['size']) :
        \Scoop\Context::inject('\Scoop\Bootstrap\Environment')->getConfig('page.size', 12);
        unset($params['page'], $params['size']);
        $paginated = new \Scoop\Persistence\SQO($this->bind($params), 'page', $this->connection);
        $result = $paginated->read()->limit($page * $size, $size)->run($params)->fetchAll();
        return $paginated->read(array('total' => 'COUNT(*)'))->run($params)
        ->fetch(\PDO::FETCH_ASSOC) + compact('page', 'size', 'result');
    }

    protected function getOrder()
    {
        if (empty($this->order)) {
            return '';
        }
        foreach ($this->order as $key => $element) {
            if (!preg_match('/\sASC|DESC$/i', $element)) {
                $this->order[$key] = $element . $this->orderType;
            }
        }
        return ' ORDER BY ' . implode(', ', $this->order);
    }

    protected function getLimit()
    {
        if ($this->offset === null) {
            return '';
        }
        $connection = $this->sqo->getConnection();
        if ($connection->is('mssql')) {
            if (empty($this->order)) {
                throw new \LogicException('SQL Server requires ORDER BY with OFFSET');
            }
            return ' OFFSET ' . $this->offset . ' ROWS' . ($this->limit === null ? '' : ' FETCH NEXT ' . $this->limit . ' ROWS ONLY');
        }
        return ' LIMIT ' . ($this->limit === null ? $this->offset : $this->limit . ' OFFSET ' . $this->offset);
    }
}
