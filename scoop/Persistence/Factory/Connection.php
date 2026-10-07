<?php

namespace Scoop\Persistence\Factory;

class Connection
{
    private $context;
    private $dispatcher;

    public function __construct(
        \Scoop\Bootstrap\Environment $context,
        \Scoop\Event\Dispatcher $dispatcher
    ) {
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    public function create()
    {
        $config = $this->context->getConfig('db', array());
        $requireds = array('database', 'user');
        foreach ($requireds as $required) {
            if (!isset($config[$required])) {
                throw new \OutOfBoundsException("Property $required not found in database configuration");
            }
        }
        return new \Scoop\Persistence\Connection(
            $this->dispatcher,
            $config['database'],
            $config['user'],
            isset($config['password']) ? $config['password'] : '',
            isset($config['host']) ? $config['host'] : '127.0.0.1',
            isset($config['port']) ? $config['port'] : null,
            isset($config['driver']) ? $config['driver'] : 'pgsql'
        );
    }
}
