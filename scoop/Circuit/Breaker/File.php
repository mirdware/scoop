<?php

namespace Scoop\Circuit\Breaker;

class File extends \Scoop\Circuit\Breaker
{
    private $directory;

    public function __construct(\Scoop\Clock $clock, $directory, array $options = array())
    {
        parent::__construct($clock, $options);
        $this->directory = rtrim($directory, '/\\');
        foreach (array('locks', 'states') as $name) {
            $path = $this->directory . '/' . $name;
            if (!is_dir($path) && !@mkdir($path, 0700, true) && !is_dir($path)) {
                throw new \RuntimeException('Cannot create circuit directory: ' . $path);
            }
        }
    }

    protected function synchronize($key, callable $callback)
    {
        $name = sha1($key);
        $lockPath = $this->directory . '/locks/' . $name;
        $verifyPath = is_file($lockPath) ? $lockPath : dirname($lockPath);
        if (!is_writable($verifyPath) || !is_readable($verifyPath)) {
            throw new \RuntimeException('Cannot open circuit lock.');
        }
        $lock = fopen($lockPath, 'c+');
        if (!$lock) {
            throw new \RuntimeException('Cannot open circuit lock.');
        }
        if (!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new \RuntimeException('Cannot acquire circuit lock.');
        }
        try {
            $path = $this->directory . '/states/' . $name;
            $state = is_file($path) ? unserialize(file_get_contents($path)) : null;
            $state = call_user_func($callback, $state);
            $this->write($path, $state);
        } catch (\Exception $error) {
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $error;
        } catch (\Throwable $error) {
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $error;
        }
        flock($lock, LOCK_UN);
        fclose($lock);
        return $state;
    }

    private function write($path, $state)
    {
        $temporary = $path . '.' . uniqid('', true);
        $data = serialize($state);
        if (file_put_contents($temporary, $data) !== strlen($data)) {
            unlink($temporary);
            throw new \RuntimeException('Cannot write circuit state.');
        }
        chmod($temporary, 0600);
        if (!rename($temporary, $path)) {
            unlink($temporary);
            throw new \RuntimeException('Cannot replace circuit state.');
        }
    }
}
