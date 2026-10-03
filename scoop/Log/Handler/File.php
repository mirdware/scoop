<?php

namespace Scoop\Log\Handler;

class File
{
    private $environment;
    private $fileName;
    private $formatter;

    public function __construct(\Scoop\Bootstrap\Environment $environment, $formatter, $file = null)
    {
        $this->environment = $environment;
        $this->formatter = $formatter;
        $this->fileName = $file;
    }

    public function handle($record)
    {
        $fileName = $this->getFileName($record);
        $dir = dirname($fileName);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \UnexpectedValueException("Cannot create log directory: $dir");
        }
        return file_put_contents(
            $fileName,
            $this->formatter->format($record) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private function getFileName($record) {
        if (!$this->fileName) {
            $this->fileName = $this->environment->getStoragePath('logs')
                . $this->environment->getConfig('app.name') . '-{Y-m-d}.log';
        }
        $fileName = str_replace('{level}', $record['level'], $this->fileName);
        return preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use ($record) {
            return $record['timestamp']->format($matches[1]);
        }, $fileName);
    }
}
