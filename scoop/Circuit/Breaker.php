<?php

namespace Scoop\Circuit;

abstract class Breaker
{
    private $options;
    private $clock;

    public function __construct(\Scoop\Clock $clock, array $options)
    {
        $this->clock = $clock;
        $this->options = $options + array(
            'failureLimit' => 5, 'failureInterval' => 60,
            'retryDelay' => 30, 'probeDuration' => 30
        );
    }

    public function execute($key, callable $callback)
    {
        $state = $this->synchronize($key, function ($state) use ($key) {
            if ($state === null) {
                $state = new State();
            }
            $state->acquire($key, (float) $this->clock->now()->format('U.u'), $this->options['probeDuration']);
            return $state;
        });
        try {
            $result = call_user_func($callback);
        } catch (\Exception $error) {
            $this->recordFailure($key, $state);
            throw $error;
        } catch (\Throwable $error) {
            $this->recordFailure($key, $state);
            throw $error;
        }
        if ($state->isProbe()) {
            $this->record($key, $state, false);
        }
        return $result;
    }

    private function recordFailure($key, $permit)
    {
        try {
            $this->record($key, $permit, true);
        } catch (\Exception $error) {
        } catch (\Throwable $error) {
        }
    }

    private function record($key, $permit, $failure)
    {
        $this->synchronize($key, function ($state) use ($permit, $failure) {
            if ($state !== null) {
                $state->record($permit, $failure, (float) $this->clock->now()->format('U.u'), $this->options);
            }
            return $state;
        });
    }

    abstract protected function synchronize($key, callable $callback);
}
