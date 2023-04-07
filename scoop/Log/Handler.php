<?php

namespace Scoop\Log;

abstract class Handler
{
    public const DEFAULT_FORMAT = '%timestamp% [%level%]: %message%';

    protected function format($log)
    {
        $output = self::DEFAULT_FORMAT;
        foreach ($log as $var => $value) {
            $output = str_replace('%' . $var . '%', $value, $output);
        }
        return $output;
    }

    abstract public function handle($log);
}
