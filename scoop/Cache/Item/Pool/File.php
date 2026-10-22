<?php

namespace Scoop\Cache\Item\Pool;

class File extends \Scoop\Cache\Item\Pool
{
    private $cacheDir;
    const DIRECTORY_LEVELS = 2;
    const FILE_PERMISSIONS = 0600;
    const DIRECTORY_PERMISSIONS = 0700;
    private $cleanupInterval;
    private $cleanupLimit;
    private $cleanupIterator;
    private $nextCleanup = 0;

    public function __construct(
        $cacheDir,
        $defaultLifetime,
        $cleanupInterval,
        $cleanupLimit
    ) {
        parent::__construct($defaultLifetime);
        $this->cacheDir = rtrim($cacheDir, '/\\');
        $this->cleanupInterval = (int) $cleanupInterval;
        $this->cleanupLimit = (int) $cleanupLimit;
        foreach (array('data', 'temporary') as $directory) {
            $path = $this->cacheDir . '/' . $directory;
            if (!is_dir($path) && !@mkdir($path, self::DIRECTORY_PERMISSIONS, true) && !is_dir($path)) {
                throw new \RuntimeException('Cannot create cache directory: ' . $path);
            }
        }
    }

    public function prune()
    {
        $this->cleanupIterator = null;
        $now = time();
        $prunedAnything = false;
        $iterator = $this->getIterator();
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                continue;
            }
            $filePath = $file->getRealPath();
            if ($file->isDir()) {
                $dirContents = new \FilesystemIterator($filePath);
                if (!$dirContents->valid()) {
                    if (@rmdir($filePath)) {
                        $prunedAnything = true;
                    }
                }
            } else {
                $dataToUnserialize = $this->unserialize($filePath);
                if (is_array($dataToUnserialize) && isset($dataToUnserialize['expiration']) &&
                    $now >= $dataToUnserialize['expiration']) {
                    if (@unlink($filePath)) {
                        $prunedAnything = true;
                    }
                }
            }
        }
        return $prunedAnything;
    }

    protected function fetch($key)
    {
        $this->maintain();
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
        $this->maintain();
        $filePath = $this->getFilePath($item->getKey());
        $dir = dirname($filePath);
        if (!is_dir($dir) && !@mkdir($dir, self::DIRECTORY_PERMISSIONS, true) && !is_dir($dir)) {
            return false;
        }
        $state = $item->getState();
        $dataToSerialize = array(
            'value' => $state->value,
            'expiration' => $state->expiration ? $state->expiration->getTimestamp() : null
        );
        $serializedData = serialize($dataToSerialize);
        $temporaryDir = $this->cacheDir . '/temporary';
        if (!is_dir($temporaryDir) || !is_writable($temporaryDir) || is_dir($filePath)) {
            return false;
        }
        $tmpFile = $temporaryDir . '/' . uniqid(basename($filePath) . '_', true);
        $stream = @fopen($tmpFile, 'xb');
        if (!$stream) {
            return false;
        }
        $success = chmod($tmpFile, self::FILE_PERMISSIONS) &&
            fwrite($stream, $serializedData) === strlen($serializedData);
        fclose($stream);
        if (!$success) {
            unlink($tmpFile);
            return false;
        }
        if (!@rename($tmpFile, $filePath)) {
            unlink($tmpFile);
            return false;
        }
        return true;
    }

    protected function removeAll()
    {
        $this->cleanupIterator = null;
        $success = true;
        $iterator = $this->getIterator();
        foreach ($iterator as $file) {
            if ($file->isDir() && !$file->isLink()) {
                @rmdir($file->getRealPath());
            } else {
                if (!@unlink($file->getPathname())) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    private function getFilePath($key)
    {
        $hash = sha1($key);
        $path = $this->cacheDir . '/data';
        for ($i = 0; $i < self::DIRECTORY_LEVELS; ++$i) {
            $path .= '/' . substr($hash, $i * 2, 2);
        }
        return "$path/$hash";
    }

    private function maintain()
    {
        if ($this->cleanupInterval < 1 || time() < $this->nextCleanup) {
            return;
        }
        $now = time();
        $this->nextCleanup = $now + $this->cleanupInterval;
        $lockPath = $this->cacheDir . '/.cleanup';
        $verifyPath = is_file($lockPath) ? $lockPath : $this->cacheDir;
        if (!is_writable($verifyPath) || !is_readable($verifyPath)) {
            return;
        }
        $lock = fopen($lockPath, 'c+');
        if (!$lock) {
            return;
        }
        chmod($lockPath, self::FILE_PERMISSIONS);
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return;
        }
        $next = (int) stream_get_contents($lock);
        if ($next <= $now) {
            try {
                if (!$this->cleanupIterator || !$this->cleanupIterator->valid()) {
                    $this->cleanupIterator = $this->getIterator();
                    $this->cleanupIterator->rewind();
                }
                $remaining = $this->cleanupLimit;
                while ($remaining-- > 0 && $this->cleanupIterator->valid()) {
                    $file = $this->cleanupIterator->current();
                    $this->cleanupIterator->next();
                    if (!$file->isFile() || $file->isLink()) {
                        continue;
                    }
                    $path = $file->getPathname();
                    $data = $this->unserialize($path);
                    if (is_array($data) && isset($data['expiration']) && $data['expiration'] <= $now) {
                        @unlink($path);
                    }
                }
            } catch (\UnexpectedValueException $ex) {
                $this->cleanupIterator = null;
            }
            rewind($lock);
            ftruncate($lock, 0);
            fwrite($lock, (string) $this->nextCleanup);
            fflush($lock);
        } else {
            $this->nextCleanup = $next;
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    private function unserialize($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        $fp = @fopen($filePath, 'rb');
        if (!$fp) return null;
        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return null;
        }
        $serializedData = '';
        while (!feof($fp)) {
            $serializedData .= fread($fp, 8192);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        if (strncmp($serializedData, 'a:', 2) !== 0) {
            return null;
        }
        $data = unserialize($serializedData);
        if (!key_exists('value', $data)) {
            return null;
        }
        return $data;
    }

    private function getIterator()
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir . '/data', \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }
}
