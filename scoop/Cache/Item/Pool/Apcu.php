<?php

namespace Scoop\Cache\Item\Pool;

class Apcu extends \Scoop\Cache\Item\Pool
{
    private $prefix;

    public function __construct($namespace, $defaultLifetime)
    {
        parent::__construct($defaultLifetime);
        $this->prefix = 'scoop.cache.' . sha1($namespace) . '.';
    }

    public function prune()
    {
        return false;
    }

    protected function fetch($key)
    {
        $success = false;
        $data = \apcu_fetch($this->prefix . $key, $success);
        if (!$success) {
            return null;
        }
        $expiration = null;
        if ($data['expiration'] !== null) {
            $expiration = new \DateTime();
            $expiration->setTimestamp($data['expiration']);
        }
        return new \Scoop\Cache\Item($key, $expiration, $data['value'], true);
    }

    protected function add(\Scoop\Cache\Item $item)
    {
        $state = $item->getState();
        $expiration = $state->expiration ? $state->expiration->getTimestamp() : null;
        $ttl = $expiration === null ? 0 : $expiration - time();
        if ($expiration !== null && $ttl <= 0) {
            return $this->remove($item->getKey());
        }
        return \apcu_store($this->prefix . $item->getKey(), array(
            'value' => $state->value,
            'expiration' => $expiration
        ), $ttl);
    }

    protected function remove($key)
    {
        $key = $this->prefix . $key;
        return \apcu_delete($key) || !\apcu_exists($key);
    }

    protected function removeAll()
    {
        return \apcu_delete(new \APCUIterator('/^' . preg_quote($this->prefix, '/') . '/', APC_ITER_KEY));
    }
}
