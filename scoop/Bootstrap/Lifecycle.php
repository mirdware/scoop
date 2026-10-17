<?php

namespace Scoop\Bootstrap;

class Lifecycle
{
    private $resources = array();

    public function register($phase, $resource, $method, $priority = 0)
    {
        $key = spl_object_hash($resource) . ':' . $method;
        $this->resources[$phase][$priority][$key] = array($resource, $method);
        return $this;
    }

    public function execute(\Scoop\Bootstrap\Application $app, $phase, $errorBurbble)
    {
        if (!$app->isShuttingDown()) {
            throw new \RuntimeException('The application is not shutting down.');
        }
        $executed = array();
        while (($task = $this->nextTask($phase, $executed))) {
            try {
                call_user_func($task);
            } catch (\Exception $ex) {
                if ($errorBurbble) {
                    throw $ex;
                }
                error_log($ex);
            } catch (\Throwable $ex) {
                if ($errorBurbble) {
                    throw $ex;
                }
                error_log($ex);
            }
        }
        if ($phase === 'terminate') {
            $this->resources = array();
        }
    }

    private function nextTask($phase, &$executed)
    {
        if (!isset($this->resources[$phase])) {
            return;
        }
        ksort($this->resources[$phase], SORT_NUMERIC);
        foreach ($this->resources[$phase] as $tasks) {
            foreach ($tasks as $key => $task) {
                if (!isset($executed[$key])) {
                    $executed[$key] = true;
                    return $task;
                }
            }
        }
    }
}
