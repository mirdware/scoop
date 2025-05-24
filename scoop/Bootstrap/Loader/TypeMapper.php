<?php

namespace Scoop\Bootstrap\Loader;

class TypeMapper
{
    private $storagePath;
    private $instances;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $storagePath = $environment->getConfig('storage', 'app/storage');
        $this->storagePath = rtrim($storagePath, '/') . '/cache/project/';
        $this->instances = array();
    }

    public function load($type)
    {
        if (isset($this->instances[$type])) {
            return $this->instances[$type];
        }
        $files = DEBUG_MODE && is_readable('composer.json') ? $this->scanTypes() : glob("{$this->storagePath}*types.php");
        $typeNormalized = $this->storagePath . str_replace('\\', '_', $type);
        foreach ($files as $file) {
            $fileName = substr($file, 0, -9);
            if (strpos($typeNormalized, $fileName) === 0) {
                $map = require $file;
                if (isset($map[$type])) {
                    $this->instances[$type] = array();
                    foreach ($map[$type] as $className) {
                        $this->instances[$type][] = \Scoop\Context::inject($className);
                    }
                    return $this->instances[$type];
                }
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
            if (strpos($namespace, 'Scoop\\') !== 0) {
                $directory = rtrim($directory, '/') . '/';
                $prefix = str_replace('\\', '_', $namespace);
                $scanner = new \Scoop\Bootstrap\Scanner\Type($directory, $prefix);
                $scanner->scan();
                $scannedTypes[] = $scanner->getCacheFilePath();
            }
        }
        return $scannedTypes;
    }
}
