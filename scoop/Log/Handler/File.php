<?php

namespace Scoop\Log\Handler;

class File
{
    private $fileName;
    private $formatter;

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
        return file_put_contents($this->fileName, $this->formatter->format($log) . PHP_EOL, FILE_APPEND);
    }
}
