<?php

namespace Scoop\Bootstrap\Loader;

class TypeMapper
{
    private $pattern;
    
    public function __construct()
    {
        $storagePath = \Scoop\Context::getEnvironment()->getConfig('storage', 'app/storage/');
        $storagePath = rtrim($storagePath, '/') . '/';
        $this->pattern = "{$storagePath}cache/project/*types.php";
    }
    public function load($type)
    {
        $files = DEBUG_MODE ? $this->scanTypes() : glob($this->pattern);
        foreach ($files as $file) {
            $map = require $file;
            if (isset($map[$type])) {
                return $map[$type];
            }
        }
        return array();
    }

    private function scanTypes()
    {
        $composerJson = json_decode(file_get_contents('composer.json'), true);
        $psr4 = $composerJson['autoload']['psr-4'];
        $scannedTypes = array();
        foreach ($psr4 as $namespace => $directory) {
            if (strpos($namespace, 'Scoop\\') === 0) {
                continue;
            }
            $directory = rtrim($directory, '/') . '/';
            $scanner = new \Scoop\Bootstrap\Scanner\Type($directory);
            $scannedTypes[] = $scanner->scan();
        }
        return $scannedTypes;
    }
}
