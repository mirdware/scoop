<?php

namespace Scoop\Log\Handler;

class Standard
{
    private $formatter;
    private $streamName;
    private $resource;

    public function __construct($formatter, $error = false)
    {
        $this->formatter = $formatter;
        $this->streamName = $error ? 'php://stderr' : 'php://stdout';
    }

    public function handle($log)
    {
        if (!is_resource($this->resource)) {
            $this->resource = fopen($this->streamName, 'w');
        }
        return fwrite($this->resource, $this->formatter->format($log) . PHP_EOL);
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
