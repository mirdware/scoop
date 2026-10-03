<?php

namespace Scoop\Log;

class Formatter
{
    public function format($log)
    {
        $log['timestamp'] = $log['timestamp']->format('c');
        $json = json_encode($log);
        if ($json === false) {
            $log['context'] = null;
            $json = json_encode($log);
            if ($json === false) {
                $log['message'] = '[message unavailable]';
                $json = json_encode($log);
            }
        }
        return $json;
    }
}
