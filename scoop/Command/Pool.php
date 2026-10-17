<?php

namespace Scoop\Command;

class Pool
{
    private $signalHandlers = array();
    private $windowsHandler = false;
    private $workers = array();
    private $activeWorkers = array();
    private $running = false;
    private $shutdownTimeout;
    private $maxDelay;
    private $dispatcher;

    public function __construct(\Scoop\Event\Dispatcher $dispatcher, $shutdownTimeout = 120, $maxDelay = 60)
    {
        $this->shutdownTimeout = $shutdownTimeout;
        $this->maxDelay = $maxDelay;
        $this->dispatcher = $dispatcher;
    }

    public function add($command, $processes = 1, $policy = 'always')
    {
        if (!in_array($policy, array('always', 'never', 'on-failure'), true)) {
            throw new \InvalidArgumentException('Unknown restart policy');
        }
        $new = clone $this;
        for ($i = 0; $i < $processes; $i++) {
            $new->workers[] = new Pool\Worker($command, $i, $policy);
        }
        return $new;
    }

    public function run()
    {
        if (!$this->workers) {
            return;
        }
        $this->running = true;
        $this->activeWorkers = array();
        try {
            if (function_exists('pcntl_signal')) {
                foreach (array(SIGTERM, SIGINT) as $signal) {
                    $handler = SIG_DFL;
                    if (function_exists('pcntl_signal_get_handler')) {
                        $handler = pcntl_signal_get_handler($signal);
                    }
                    $this->signalHandlers[$signal] = $handler;
                    pcntl_signal($signal, array($this, 'stop'));
                }
            } elseif (function_exists('sapi_windows_set_ctrl_handler')) {
                $this->windowsHandler = sapi_windows_set_ctrl_handler(array($this, 'stop'));
            }
            foreach ($this->workers as $worker) {
                $this->activeWorkers[] = clone $worker;
            }
            while ($this->running) {
                $pending = false;
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                foreach ($this->activeWorkers as $worker) {
                    if ($this->running) {
                        $this->tick($worker);
                        if (!$worker->isFinished()) {
                            $pending = true;
                        }
                    }
                }
                if (!$pending) {
                    $this->running = false;
                }
                if ($this->running) {
                    usleep(100000);
                }
            }
        } catch (\Exception $exception) {
            $this->finish();
            throw $exception;
        } catch (\Throwable $exception) {
            $this->finish();
            throw $exception;
        }
        $this->finish();
    }

    private function tick(Pool\Worker $worker)
    {
        if ($worker->isFinished() || Pool\Worker::now() < $worker->getNext()) {
            return;
        }
        $event = 'Scoop\Command\Event\ProcessStarted';
        if ($worker->hasProcess()) {
            if ($worker->isRunning()) {
                return;
            }
            $worker->close();
            $event = 'Scoop\Command\Event\ProcessExited';
        } elseif (!$worker->start()) {
            $event = 'Scoop\Command\Event\ProcessFailed';
        }
        $this->dispatcher->dispatch(new $event($worker));
        if ($event !== 'Scoop\Command\Event\ProcessStarted') {
            $this->retry($worker);
        }
    }

    private function retry(Pool\Worker $worker)
    {
        if (
            $worker->getPolicy() === 'never'
            || ($worker->getPolicy() === 'on-failure' && $worker->getExitCode() === 0)
        ) {
            $worker->complete();
            return;
        }
        $lived = Pool\Worker::now() - $worker->getStarted();
        $delay = $lived < 5 ? min($worker->getDelay() * 2, $this->maxDelay) : 1;
        $worker->scheduleRestart($delay);
    }

    private function stop()
    {
        $this->running = false;
    }

    private function finish()
    {
        $this->running = false;
        $stopedEvents = array();
        try {
            foreach ($this->activeWorkers as $worker) {
                $worker->terminate();
            }
            $deadline = Pool\Worker::now() + $this->shutdownTimeout;
            while (Pool\Worker::now() < $deadline && $this->alive()) {
                usleep(100000);
            }
            foreach ($this->activeWorkers as $worker) {
                if ($worker->hasProcess()) {
                    $worker->close();
                    $stopedEvents[] = new Event\ProcessStopped($worker);
                }
            }
            $this->activeWorkers = array();
        } catch (\Exception $exception) {
            $this->restoreSignals($stopedEvents);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->restoreSignals($stopedEvents);
            throw $exception;
        }
        $this->restoreSignals($stopedEvents);
    }

    private function alive()
    {
        foreach ($this->activeWorkers as $worker) {
            if ($worker->isRunning()) {
                return true;
            }
        }
        return false;
    }

    private function restoreSignals($stopedEvents)
    {
        foreach ($this->signalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
        $this->signalHandlers = array();
        if ($this->windowsHandler) {
            sapi_windows_set_ctrl_handler(array($this, 'stop'), false);
            $this->windowsHandler = false;
        }
        foreach ($stopedEvents as $stopedEvent) {
            $this->dispatcher->dispatch($stopedEvent);
        }
    }
}
