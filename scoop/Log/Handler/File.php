<?php

namespace Scoop\Log\Handler;

class File
{
    private $environment;
    private $fileName;
    private $shouldDefered;
    private $formatter;
    private $records = array();

    public function __construct(\Scoop\Bootstrap\Environment $environment, $formatter, $file = null, $shouldDefered = true)
    {
        $this->environment = $environment;
        $this->shouldDefered = $shouldDefered;
        $this->formatter = $formatter;
        $this->fileName = $file;
    }

    public function handle($record)
    {
        if ($this->shouldDefered) {
            $this->records[] = $record;
            return true;
        }
        $fileName = $this->getFileName($record);
        $content = $this->formatter->format($record) . PHP_EOL;
        if (!file_put_contents($fileName, $content, FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException("Cannot write to log file: $fileName");
        }
    }

    public function flush()
    {
        if (empty($this->records)) {
            return;
        }
        $records = $this->records;
        $this->records = array();
        $contents = array();
        try {
            foreach ($records as $record) {
                $fileName = $this->getFileName($record);
                if (isset($contents[$fileName])) {
                    $contents[$fileName] .= $this->formatter->format($record) . PHP_EOL;
                } else {
                    $contents[$fileName] = $this->formatter->format($record) . PHP_EOL;
                }
            }
            foreach ($contents as $fileName => $content) {
                if (!file_put_contents($fileName, $content, FILE_APPEND | LOCK_EX)) {
                    throw new \RuntimeException("Cannot write to log file: $fileName");
                }
            }
        } catch (\Exception $ex) {
            error_log($ex);
        } catch (\Throwable $ex) {
            error_log($ex);
        }
    }

    private function getFileName($record) {
        if (!$this->fileName) {
            $this->fileName = $this->environment->getStoragePath('logs')
                . $this->environment->getConfig('app.name') . '-{Y-m-d}.log';
        }
        $fileName = str_replace('{level}', $record['level'], $this->fileName);
        $fileName = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use ($record) {
            return $record['timestamp']->format($matches[1]);
        }, $fileName);
        $dir = dirname($fileName);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \UnexpectedValueException("Cannot create log directory: $dir");
        }
        return $fileName;
    }
}
