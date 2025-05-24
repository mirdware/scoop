<?php

namespace Scoop\Command\Handler\Cache;

class Cleaner
{
    private $cache;
    private $writer;
    private $viewStorage;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Cache\Item\Pool $cache,
        \Scoop\Bootstrap\Environment $environment
    ) {
        $this->writer = $writer;
        $this->cache = $cache;
        $this->viewStorage = $environment->getConfig('storage', 'app/storage');
    }

    public function execute()
    {
        $this->deleteDirectory($this->viewStorage . '/cache/views/');
        $this->deleteDirectory($this->viewStorage . '/cache/project/');
        $this->deleteDirectory($this->viewStorage . '/cache/json/');
        $this->cache->clear();
        $this->writer->write('Cache cleaned successfully.');
    }

    public function help()
    {
        $this->writer->write('Completely removes all cached data, including file-based caches.');
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $items = scandir($dir);
        if (!$items) {
            return false;
        }
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    if (!$this->deleteDirectory($path)) {
                        return false;
                    }
                } elseif (!unlink($path)) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }
}
