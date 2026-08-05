<?php

namespace Scoop\Bootstrap;

abstract class Scanner
{
    private $metaFilePath;
    private $cacheFilePaths;
    private $filePattern;
    private $directory;
    private $map;

    public function __construct($directory, $filePattern, $cacheFilePaths, $metaFilePath)
    {
        $this->filePattern = $filePattern;
        $this->cacheFilePaths = $cacheFilePaths;
        $this->metaFilePath = $metaFilePath;
        $this->directory = rtrim($directory, '/') . '/';
    }

    public function scan()
    {
        $this->map = array();
        $isModified = $this->analyzeDirectory();
        if ($isModified) {
            $cache = $this->build($this->map);
            foreach ($this->cacheFilePaths as $name => $filePath) {
                $this->save($cache[$name], $filePath);
            }
            $this->save($this->map, $this->metaFilePath);
        }
        return $isModified;
    }

    public function getCacheFilePath($name)
    {
        if (!isset($this->cacheFilePaths[$name])) {
            throw new \OutOfBoundsException("Cache $name not found");
        }
        return $this->cacheFilePaths[$name];
    }

    public function getMetaFilePath()
    {
        return $this->metaFilePath;
    }

    public function getDirectory()
    {
        return $this->directory;
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
        $meta = !$isModified ? require $this->metaFilePath : array();
        foreach ($regex as $fileInfo) {
            $fileName = str_replace(DIRECTORY_SEPARATOR, '/', $fileInfo->getPathname());
            $fileTime = $fileInfo->getMTime();
            if (isset($meta[$fileName]) && $meta[$fileName]['time'] === $fileTime) {
                $this->map[$fileName] = $meta[$fileName];
            } else {
                $fileData = $this->check($fileName);
                $fileData['time'] = $fileTime;
                $this->map[$fileName] = $fileData;
                $isModified = true;
            }
        }
        return $isModified || count($meta) !== count($this->map);
    }

    abstract protected function check($filePath);

    abstract protected function build($map);
}
