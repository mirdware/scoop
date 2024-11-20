<?php

namespace Scoop\Bootstrap;

abstract class Scanner
{
    private $metaFilePath;
    private $cacheFilePath;
    private $filePattern;
    protected $directory;
    protected $map;

    public function __construct($directory, $filePattern, $cacheFilePath, $metaFilePath)
    {
        $this->filePattern = $filePattern;
        $this->cacheFilePath = $cacheFilePath;
        $this->metaFilePath = $metaFilePath;
        $this->directory = rtrim($directory, '/') . '/';
    }

    public function scan()
    {
        $this->map = array();
        $isModified = $this->analyzeDirectory();
        if ($isModified) {
            $this->save($this->map, $this->metaFilePath);
            $this->save($this->buildMap(), $this->cacheFilePath);
        }
        return $this->cacheFilePath;
    }
    
    private function save($data, $filePath)
    {
        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($filePath, $content);
    }

    private function analyzeDirectory() {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory));
        $regex = new \RegexIterator($iterator, $this->filePattern);
        $isModified = !is_readable($this->metaFilePath);
        $meta = !$isModified ? require $this->metaFilePath : [];
        foreach ($regex as $fileInfo) {
            $fileName = $fileInfo->getPathname();
            $fileTime = $fileInfo->getMTime();
            if (isset($meta[$fileName]) && $meta[$fileName]['time'] === $fileTime) {
                $this->map[$fileName] = $meta[$fileName];
            } else {
                $fileData = $this->checkFile($fileName);
                $fileData['time'] = $fileTime;
                $this->map[$fileName] = $fileData;
                $isModified = true;
            }
        }
        return $isModified || count($meta) !== count($this->map);
    }

    protected function getPath($path, $fileName)
    {
        $storagePath = \Scoop\Context::getEnvironment()->getConfig('storage', 'app/storage/');
        $path = rtrim($storagePath, '/') . $path;
        if (!is_dir($path)) {
            mkdir($path, 755, true);
        }
        return $path . $fileName;
    }

    abstract protected function checkFile($filePath);
    
    abstract protected function buildMap();
}
