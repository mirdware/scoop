<?php

namespace Scoop\Command\Pool;

class Worker
{
    private $name;
    private $replica;
    private $command;
    private $process;
    private $pid;
    private $started = 0;
    private $delay = 1;
    private $next = 0;
    private $exitCode;
    private $policy;
    private $finished = false;
    private $starts = 0;

    public function __construct($name, array $command, $replica, $policy)
    {
        $this->name = $name;
        $this->command = array_values($command);
        $this->replica = $replica;
        $this->policy = $policy;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPolicy()
    {
        return $this->policy;
    }

    public function getStartCount()
    {
        return $this->starts;
    }

    public function isFinished()
    {
        return $this->finished;
    }

    public function complete()
    {
        $this->finished = true;
    }

    public function getReplica()
    {
        return $this->replica;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getStarted()
    {
        return $this->started;
    }

    public function getDelay()
    {
        return $this->delay;
    }

    public function getNext()
    {
        return $this->next;
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function hasProcess()
    {
        return is_resource($this->process);
    }

    public function isRunning()
    {
        if (!$this->hasProcess()) {
            return false;
        }
        $status = proc_get_status($this->process);
        $this->pid = $status['pid'];
        if (!$status['running'] && $this->exitCode === null) {
            $this->exitCode = $status['exitcode'];
        }
        return $status['running'];
    }

    public function start()
    {
        $this->started = self::now();
        $this->pid = null;
        $this->exitCode = null;
        $command = $this->command;
        if (PHP_VERSION_ID < 70400) {
            $command = DIRECTORY_SEPARATOR === '\\' ?
                implode(' ', array_map(array($this, 'quoteWindowsArgument'), $command)) :
                'exec ' . implode(' ', array_map(array($this, 'quotePosixArgument'), $command));
        }
        $options = array('bypass_shell' => true);
        if (PHP_VERSION_ID >= 70400) {
            $options['create_process_group'] = true;
        }
        $pipes = array();
        $this->process = proc_open($command, array(
            0 => array('file', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null', 'r'),
            1 => STDOUT,
            2 => STDERR,
        ), $pipes, null, null, $options) ?: null;
        if (!$this->hasProcess()) {
            return false;
        }
        $this->isRunning();
        $this->starts++;
        return true;
    }

    public function scheduleRestart($delay)
    {
        $this->delay = $delay;
        $this->next = self::now() + $delay;
    }

    public function terminate()
    {
        if ($this->isRunning()) {
            $sent = false;
            if (function_exists('sapi_windows_generate_ctrl_event')) {
                $sent = @sapi_windows_generate_ctrl_event(PHP_WINDOWS_EVENT_CTRL_BREAK, $this->pid);
            }
            if (!$sent) {
                proc_terminate($this->process, 15);
            }
        }
    }

    public function close()
    {
        if ($this->hasProcess()) {
            if ($this->isRunning()) {
                proc_terminate($this->process, 9);
            }
            $exitCode = proc_close($this->process);
            $this->process = null;
            if ($this->exitCode === null) {
                $this->exitCode = $exitCode;
            }
        }
    }

    public static function now()
    {
        return function_exists('hrtime') ? hrtime(true) / 1000000000 : microtime(true);
    }

    private function quotePosixArgument($argument)
    {
        return "'" . str_replace("'", "'\\''", $argument) . "'";
    }

    private function quoteWindowsArgument($argument)
    {
        $quoted = '"';
        $slashes = 0;
        for ($i = 0, $length = strlen($argument); $i < $length; $i++) {
            if ($argument[$i] === '\\') {
                $slashes++;
                continue;
            }
            $quoted .= str_repeat('\\', $slashes * ($argument[$i] === '"' ? 2 : 1));
            $quoted .= $argument[$i] === '"' ? '\\"' : $argument[$i];
            $slashes = 0;
        }
        return $quoted . str_repeat('\\', $slashes * 2) . '"';
    }
}
