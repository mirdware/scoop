<?php

namespace Scoop\Cache;

class Item
{
    protected $key;
    protected $value;
    protected $isHit;
    protected $expiration;
    protected $hasPendingChanges;

    public function __construct(
        $key,
        \DateTime $expirationFromPool = null,
        $valueFromPool = null,
        $wasFoundAndValidInPool = false
    ) {
        $this->validateKey($key);
        $this->key = $key;
        $this->hasPendingChanges = false;
        $this->expiration = $expirationFromPool ? clone $expirationFromPool : null;
        if ($wasFoundAndValidInPool) {
            $this->value = $valueFromPool;
            $this->isHit = true;
        } else {
            $this->value = null;
            $this->isHit = false;
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }

    public function isHit()
    {
        if (!$this->hasPendingChanges && !$this->isHit) {
            return false;
        }
        if ($this->expiration === null) {
            return true;
        }
        $now = new \DateTime();
        return $now < $this->expiration;
    }

    public function set($value)
    {
        $this->value = $value;
        $this->hasPendingChanges = true;
        return $this;
    }

    public function expiresAt($expiration)
    {
        $this->expiration = $expiration ? \DateTime::createFromInterface($expiration) : null;
        $this->hasPendingChanges = true;
        return $this;
    }

    public function expiresAfter($time)
    {
        $this->expiration = null;
        if (is_int($time)) {
            $time = \DateInterval::createFromDateString($time <= 0 ? '-1 second' : "+{$time} seconds");
        }
        if ($time instanceof \DateInterval) {
            $date = new \DateTime();
            $this->expiration = $date->add($time);
        }
        $this->hasPendingChanges = true;
        return $this;
    }

    public function getExpiration()
    {
        return $this->expiration;
    }

    private function validateKey($key)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException(
                "Cache key must be a string, " . gettype($key) . " given."
            );
        }
        if (empty($key)) {
            throw new \InvalidArgumentException("Cache key cannot be empty.");
        }
        if (preg_match('/[{}()\/@:]/', $key)) {
            throw new \InvalidArgumentException(
                "Invalid key: '{$key}' contains reserved characters."
            );
        }
    }
}
