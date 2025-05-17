<?php

namespace Scoop\Cache\Item\Pool;

class File extends \Scoop\Cache\Item\Pool
{
    private $cacheDir;
    private $directoryLevels;
    private $fileExtension;
    private $filePermissions;
    private $dirPermissions;

    public function __construct(
        $cacheDir,
        $defaultLifetime = 0,
        $directoryLevels = 2,
        $fileExtension = 'cache',
        $filePermissions = 0664,
        $dirPermissions = 0775
    ) {
        parent::__construct($defaultLifetime);
        $this->cacheDir = rtrim($cacheDir, '/\\');
        $this->directoryLevels = max(0, (int) $directoryLevels);
        $this->fileExtension = $fileExtension;
        $this->filePermissions = $filePermissions;
        $this->dirPermissions = $dirPermissions;
        if (!is_dir($this->cacheDir) && !@mkdir($this->cacheDir, $this->dirPermissions, true)) {
            throw new \RuntimeException("Cache directory '{$this->cacheDir}' does not exist and could not be created.");
        }
        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException("Cache directory '{$this->cacheDir}' is not writable.");
        }
    }

    public function prune()
    {
        $now = time();
        $prunedAnything = false;
        $iterator = $this->getIterator();
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            if ($file->isDir()) {
                $dirContents = new \FilesystemIterator($filePath);
                if (!$dirContents->valid()) {
                    if (@rmdir($filePath)) {
                        $prunedAnything = true;
                    }
                }
            } else {
                if ($file->getExtension() === $this->fileExtension) {
                    $dataToUnserialize = $this->unserialize($filePath);
                    if (is_array($dataToUnserialize) && isset($dataToUnserialize['expiration']) &&
                        $dataToUnserialize['expiration'] !== null && $now >= $dataToUnserialize['expiration']) {
                        if (@unlink($filePath)) {
                            $prunedAnything = true;
                        }
                    }
                }
            }
        }
        return $prunedAnything;
    }

    protected function fetch($key)
    {
        $filePath = $this->getFilePath($key);
        $data = $this->unserialize($filePath);
        if ($data === null) {
            return null;
        }
        $expirationTimestamp = $data['expiration'];
        $expirationDateTime =  null;
        if ($expirationTimestamp !== null) {
            if (time() >= $expirationTimestamp) {
                @unlink($filePath);
                return null;
            }
            $expirationDateTime = new \DateTime();
            $expirationDateTime->setTimestamp($expirationTimestamp);
        }
        return new \Scoop\Cache\Item($key, $expirationDateTime, $data['value'], true);
    }

    protected function remove($key)
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    protected function add(\Scoop\Cache\Item $item)
    {
        $filePath = $this->getFilePath($item->getKey());
        $dir = dirname($filePath);
        if (!is_dir($dir) && !@mkdir($dir, $this->dirPermissions, true)) {
            return false;
        }
        $expirationDateTime = $item->getExpiration();
        $expirationTimestamp = $expirationDateTime ? $expirationDateTime->getTimestamp() : null;
        $dataToSerialize = array(
            'value' => $item->get(),
            'expiration' => $expirationTimestamp
        );
        $serializedData = serialize($dataToSerialize);
        $tmpFile = $dir . '/' . uniqid(basename($filePath) . '_', true) . '.tmp';
        if (@file_put_contents($tmpFile, $serializedData, LOCK_EX) === false) {
            @unlink($tmpFile);
            return false;
        }
        if (!@rename($tmpFile, $filePath)) {
            @unlink($tmpFile);
            return false;
        }
        return true;
    }

    protected function removeAll()
    {
        $success = true;
        $iterator = $this->getIterator();
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                if ($file->getExtension() === $this->fileExtension) {
                    if (!@unlink($file->getRealPath())) {
                        $success = false;
                    }
                }
            }
        }
        return $success;
    }

    private function getFilePath($key)
    {
        $hash = sha1($key);
        $path = $this->cacheDir;
        if ($this->directoryLevels > 0) {
            for ($i = 0; $i < $this->directoryLevels; ++$i) {
                $path .= '/' . substr($hash, $i * 2, 2);
            }
        }
        return "$path/$hash.{$this->fileExtension}";
    }

    private function unserialize($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        $fp = @fopen($filePath, 'rb');
        if (!$fp) return null;
        if (!flock($fp, LOCK_SH)) {
            @fclose($fp);
            return null;
        }
        $serializedData = '';
        while (!feof($fp)) {
            $serializedData .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        @fclose($fp);
        if ($serializedData === '') {
            return null;
        }
        return @unserialize($serializedData);
    }

    private function getIterator()
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }
}
