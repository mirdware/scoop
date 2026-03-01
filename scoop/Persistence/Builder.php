<?php

namespace Scoop\Persistence;

class Builder
{
    public function build($table, $alias = '', $connection = 'default')
    {
        return new SQO($table, $alias, $connection);
    }
}
