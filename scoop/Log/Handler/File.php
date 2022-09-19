<?php
namespace Scoop\Log\Handler;

class File extends \Scoop\Log\Handler
{
    private $name;

    public function __construct($name = null)
    {
        if (!$name) {
            $appName = \Scoop\Context::getEnvironment()->getConfig('app.name');
            $name = 'app/logs/'.$appName.'-'.date('Y-m-d').'.log';
        }
        $dir = dirname($name);
        if (!file_exists($dir)) {
            if (mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s"', $dir));
            }
        }
        $this->name = $name;
    }

    public function handle($log)
    {
        return file_put_contents($this->name, $this->format($log).PHP_EOL, FILE_APPEND);
    }
}
