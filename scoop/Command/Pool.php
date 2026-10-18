<?php

namespace Scoop\Command;

class Pool
{
    private $signalHandlers = array();
    private $windowsHandler = false;
    private $workers = array();
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

    public function run($workers)
    {
        $this->workers = array();
        foreach ($workers as $name => $worker) {
            if (!isset($worker['command']) || !is_array($worker['command'])) {
                throw new \InvalidArgumentException("Worker '$name' must define a command array.");
            }
            $command = $worker['command'];
            $policy = 'always';
            $processes = 1;
            if (isset($worker['policy'])) {
                $policy = $worker['policy'];
                if (!in_array($policy, array('never', 'on-failure', 'always'), true)) {
                    throw new \InvalidArgumentException("Worker '$name' has an invalid restart policy.");
                }
            }
            if (isset($worker['processes']) && is_int($worker['processes']) && $worker['processes'] > 0) {
                $processes = $worker['processes'];
            }
            for ($i = 0; $i < $processes; $i++) {
                $this->workers[] = new Pool\Worker($name, $command, $i, $policy);
            }
        }
        if (empty($this->workers)) {
            return;
        }
        $this->running = true;
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
            while ($this->running) {
                $pending = false;
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                foreach ($this->workers as $worker) {
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
        $policy = $worker->getPolicy();
        $code = $worker->getExitCode();
        if ($policy === 'never' || ($policy === 'on-failure' && $code === 0)) {
            return $worker->complete();
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
            foreach ($this->workers as $worker) {
                $worker->terminate();
            }
            $deadline = Pool\Worker::now() + $this->shutdownTimeout;
            while (Pool\Worker::now() < $deadline && $this->alive()) {
                usleep(100000);
            }
            foreach ($this->workers as $worker) {
                if ($worker->hasProcess()) {
                    $worker->close();
                    $stopedEvents[] = new Event\ProcessStopped($worker);
                }
            }
            $this->workers = array();
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
        foreach ($this->workers as $worker) {
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
