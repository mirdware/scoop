<?php

namespace Scoop\Circuit;

class State
{
    private $status;
    private $generation;
    private $failures;
    private $openUntil;
    private $probeUntil;

    public function __construct()
    {
        $this->close();
    }

    public function isProbe()
    {
        return $this->status === 'half-open';
    }

    public function getGeneration()
    {
        return $this->generation;
    }

    public function acquire($key, $now, $probeDuration)
    {
        if ($this->status !== 'closed') {
            $until = $this->status === 'open' ? $this->openUntil : $this->probeUntil;
            if ($until > $now) {
                throw new Exception\Open($key, $until - $now);
            }
            $this->reserveProbe($now, $probeDuration);
        }
    }

    public function record(State $permit, $failure, $now, array $options)
    {
        if ($this->generation !== $permit->getGeneration()) {
            return;
        }
        if ($this->status === 'half-open') {
            if ($failure) {
                $this->open($now, $options['retryDelay']);
            } else {
                $this->close();
            }
        } elseif ($this->status === 'closed' && $failure) {
            $window = $options['failureInterval'];
            $this->failures = array_values(array_filter($this->failures, function ($time) use ($now, $window) {
                return $time > $now - $window;
            }));
            $this->failures[] = $now;
            if (count($this->failures) >= $options['failureLimit']) {
                $this->open($now, $options['retryDelay']);
            }
        }
    }

    public function open($now, $retryDelay)
    {
        $this->status = 'open';
        $this->generation = uniqid('', true);
        $this->openUntil = $now + $retryDelay;
        $this->probeUntil = 0;
        $this->failures = array();
        return $this;
    }

    public function close()
    {
        $this->status = 'closed';
        $this->generation = uniqid('', true);
        $this->failures = array();
        $this->openUntil = 0;
        $this->probeUntil = 0;
        return $this;
    }

    public function reserveProbe($now, $probeDuration)
    {
        $this->status = 'half-open';
        $this->generation = uniqid('', true);
        $this->probeUntil = $now + $probeDuration;
        return $this;
    }
}
