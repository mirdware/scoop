<?php

namespace Scoop\Circuit\Breaker;

class APCu extends \Scoop\Circuit\Breaker
{
    private $prefix;

    public function __construct(\Scoop\Clock $clock, array $options = array())
    {
        parent::__construct($clock, $options);
        $namespace = isset($options['namespace']) ? $options['namespace'] : 'default';
        $this->prefix = 'scoop.circuit.' . sha1($namespace) . '.';
    }

    protected function synchronize($key, callable $callback)
    {
        $head = $this->prefix . sha1($key);
        for ($attempt = 0; $attempt < 1000; ++$attempt) {
            \apcu_add($head, 0);
            $version = \apcu_fetch($head, $found);
            if (!$found) {
                throw new \RuntimeException('Cannot read circuit version from APCu.');
            }
            $state = $version === 0 ? null : \apcu_fetch($head . '.' . $version, $found);
            if ($version !== 0 && !$found) {
                \apcu_cas($head, $version, 0);
                continue;
            }
            $state = call_user_func($callback, $state);
            $sequence = $head . '.sequence';
            \apcu_add($sequence, $version);
            $next = \apcu_inc($sequence, 1, $allocated);
            if (!$allocated || $next <= $version) {
                throw new \RuntimeException('Cannot allocate circuit version in APCu.');
            }
            $entry = $head . '.' . $next;
            if (!\apcu_add($entry, $state)) {
                throw new \RuntimeException('Cannot persist circuit state in APCu.');
            }
            if (\apcu_cas($head, $version, $next)) {
                if ($version !== 0) {
                    \apcu_delete($head . '.' . $version);
                }
                return $state;
            }
            \apcu_delete($entry);
        }
        throw new \RuntimeException('Circuit state remained busy in APCu.');
    }
}
