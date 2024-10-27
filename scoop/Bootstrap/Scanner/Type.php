<?php

namespace Scoop\Bootstrap\Scanner;

class Type extends \Scoop\Bootstrap\Scanner
{

    public function __construct($directory) {
        $prefix = str_replace('/', '_', $directory);
        $cacheFilePath = $this->getPath('/cache/project/', "{$prefix}types.php");
        $metaFilePath = $this->getPath('/cache/project/', "{$prefix}meta.php");
        parent::__construct($directory, '/\.php$/', $cacheFilePath, $metaFilePath);
    }

    protected function buildMap()
    {
        $map = array();
        foreach ($this->map as $classTyped) {
            if (isset($classTyped['types'])) {
                $className = $classTyped['class'];
                foreach ($classTyped['types'] as $typeName) {
                    if (!isset($map[$typeName])) {
                        $map[$typeName] = array();
                    }
                    $map[$typeName][] = $className;
                }
            }
        }
        return $map;
    }

    protected function checkFile($filePath) {
        if (!is_readable($filePath)) return;
        $content = file_get_contents($filePath);
        if ($content === false) return;
        $tokens = token_get_all($content);
        if ($tokens === false) return;
        $namespace = '';
        $fullClassName = '';
        $hasTypes = false;
        foreach ($tokens as $index => $token) {
            if ($tokens[$index][0] === T_NAMESPACE) {
                $namespace = $tokens[$index + 2][1] ? $tokens[$index + 2][1] : '';
            } elseif ($token[0] === T_CLASS) {
                $fullClassName = trim(($namespace ? $namespace . '\\' : '') . $tokens[$index + 2][1]);
                $hasTypes = $this->containsTypeDefinitions($tokens, $index + 2);
                break;
            }
        }
        if ($hasTypes) {
            return array(
                'class' => $fullClassName,
                'types' => $this->getTypeNames($fullClassName)
            );
        }
        return array();
    }

    private function containsTypeDefinitions($tokens, $startIndex) {
        for ($index = $startIndex; isset($tokens[$index]); $index++) {
            if (is_array($tokens[$index]) && 
                ($tokens[$index][0] === T_EXTENDS || $tokens[$index][0] === T_IMPLEMENTS)) {
                return true;
            }
        }
        return false;
    }

    private function getTypeNames($className) {
        if (!class_exists($className)) return;
        $reflection = new \ReflectionClass($className);
        $typeNames = $reflection->getInterfaceNames();
        $parentClass = $reflection->getParentClass();
        if ($parentClass) {
            $typeNames[] = $parentClass->getName();
        }
        return $typeNames;
    }
}
