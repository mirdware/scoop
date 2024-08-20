<?php

namespace Scoop\Log;

class Formatter
{
    public function format($log)
    {
        return $log['timestamp']->format('c') . ' [' . $log['level'] . ']: ' . $log['message'];
    }
}
