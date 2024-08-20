<?php

namespace Scoop\Log\Handler;

class Standard
{
    private $formatter;
    private $stream;

    public function __construct($formatter, $error = false)
    {
        $this->formatter = $formatter;
        $this->stream = $error ? 'php://stderr' : 'php://stdout';
    }

    public function handle($log)
    {
        $std = fopen($this->stream, 'w');
        fwrite($std, $this->formatter->format($log) . PHP_EOL);
        fclose($std);
    }
}
