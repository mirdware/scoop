<?php

namespace Scoop\Log\Handler;

class File extends \Scoop\Log\Handler
{
    private $fileName;

    public function __construct($file = null)
    {
        if (!$file) {
            $env = \Scoop\Context::getEnvironment();
            $file = $env->getConfig('storage', 'app/storage/')
            . 'logs/' . $env->getConfig('app.name')
            . '-' . date('Y-m-d') . '.log';
        }
        $dir = dirname($file);
        if (!file_exists($dir)) {
            if (mkdir($dir, 0700, true) && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s"', $dir));
            }
        }
        $this->fileName = $file;
    }

    public function handle($log)
    {
        return file_put_contents($this->fileName, $this->format($log) . PHP_EOL, FILE_APPEND);
    }
}
