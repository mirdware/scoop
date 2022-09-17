<?php
namespace Scoop\Log\Handler;

class File implements \Scoop\Log\Handler
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
        $output = self::DEFAULT_FORMAT;
        foreach ($log as $var => $value) {
            $output = str_replace('%' . $var . '%', $value, $output);
        }
        file_put_contents($this->name, $output . PHP_EOL, FILE_APPEND);
    }
}
