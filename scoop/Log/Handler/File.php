<?php

namespace Scoop\Log\Handler;

class File
{
    private $fileName;
    private $formatter;
    private $resource;

    public function __construct($formatter, $file)
    {
        $dir = dirname($file);
        if (!file_exists($dir)) {
            if (mkdir($dir, 0700, true) && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s"', $dir));
            }
        }
        $this->formatter = $formatter;
        $this->fileName = $file;
    }

    public function handle($log)
    {
        if (!is_resource($this->resource)) {
            $this->resource = fopen($this->fileName, 'a');
        }
        flock($this->resource, LOCK_EX);
        return fwrite($this->resource, $this->formatter->format($log) . PHP_EOL);
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
