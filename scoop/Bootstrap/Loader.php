<?php

namespace Scoop\Bootstrap;

class Loader
{
    private $prefixLengthsPsr4 = array();
    private $prefixDirsPsr4 = array();
    private $fallbackDirsPsr4 = array();
    private $classMap = array();
    private $classMapAuthoritative = false;

    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4;
    }

    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4;
    }

    public function getClassMap()
    {
        return $this->classMap;
    }

    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    public function add($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            if ($prepend) {
                $this->fallbackDirsPsr4 = array_merge(
                    (array) $paths,
                    $this->fallbackDirsPsr4
                );
            } else {
                $this->fallbackDirsPsr4 = array_merge(
                    $this->fallbackDirsPsr4,
                    (array) $paths
                );
            }
        } elseif (!isset($this->prefixDirsPsr4[$prefix])) {
            $length = strlen($prefix);
            if ($prefix[$length - 1] !== '\\') {
                throw new \InvalidArgumentException('A non-empty PSR-4 prefix must end with a namespace separator.');
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        } elseif ($prepend) {
            $this->prefixDirsPsr4[$prefix] = array_merge(
                (array) $paths,
                $this->prefixDirsPsr4[$prefix]
            );
        } else {
            $this->prefixDirsPsr4[$prefix] = array_merge(
                $this->prefixDirsPsr4[$prefix],
                (array) $paths
            );
        }
    }

    public function set($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr4 = (array) $paths;
        } else {
            $length = strlen($prefix);
            if ($prefix[$length - 1] !== '\\') {
                throw new \InvalidArgumentException('A non-empty PSR-4 prefix must end with a namespace separator.');
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        }
    }

    public function setUseIncludePath($useIncludePath)
    {
        $this->useIncludePath = $useIncludePath;
    }

    public function setClassMapAuthoritative($classMapAuthoritative)
    {
        $this->classMapAuthoritative = $classMapAuthoritative;
    }

    public function isClassMapAuthoritative()
    {
        return $this->classMapAuthoritative;
    }

    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            include $file;
            return true;
        }
    }

    public function findFile($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }
        if ($this->classMapAuthoritative) {
            return false;
        }
        $file = $this->findFileWithExtension($class, '.php');
        if ($file === null && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }
        if ($file === null) {
            return $this->classMap[$class] = false;
        }
        return $file;
    }

    private function findFileWithExtension($class, $ext)
    {
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;
        $myLocation = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $first = $class[0];
        if (isset($this->prefixLengthsPsr4[$first])) {
            foreach ($this->prefixLengthsPsr4[$first] as $prefix => $length) {
                if (strpos($class, $prefix) === 0) {
                    foreach ($this->prefixDirsPsr4[$prefix] as $dir) {
                        $file = $myLocation . $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length);
                        if (file_exists($file)) {
                            return $file;
                        }
                    }
                }
            }
        }
        foreach ($this->fallbackDirsPsr4 as $dir) {
            if (file_exists($file = $myLocation . $dir . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
                return $file;
            }
        }
    }
}
