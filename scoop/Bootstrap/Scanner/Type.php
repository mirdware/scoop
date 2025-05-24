<?php

namespace Scoop\Bootstrap\Scanner;

class Type extends \Scoop\Bootstrap\Scanner
{
    public function __construct($directory, $prefix)
    {
        $cacheFilePath = $this->getPath('/cache/project/', "{$prefix}types.php");
        $metaFilePath = $this->getPath('/cache/project/', "{$prefix}meta.php");
        parent::__construct($directory, '/\.php$/', $cacheFilePath, $metaFilePath);
    }

    protected function build($metaMap)
    {
        $typeMap = array();
        foreach ($metaMap as $classTyped) {
            if (isset($classTyped['types'])) {
                $className = $classTyped['class'];
                foreach ($classTyped['types'] as $typeName) {
                    if (!isset($typeMap[$typeName])) {
                        $typeMap[$typeName] = array();
                    }
                    $typeMap[$typeName][] = $className;
                }
            }
        }
        return $typeMap;
    }

    protected function check($filePath)
    {
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
                $namespace = $this->getNamespace($index, $tokens);
            } elseif ($token[0] === T_CLASS) {
                $fullClassName = ($namespace ? $namespace . '\\' : '') . $tokens[$index + 2][1];
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
    }

    private function getNamespace($startIndex, $tokens)
    {
        $namespace = '';
        for ($index = $startIndex + 2; isset($tokens[$index]); $index++) {
            if ($tokens[$index] === ';') {
                return ltrim($namespace, '\\');
            }
            $tokenType = defined('T_NAME_QUALIFIED') ? T_NAME_QUALIFIED : T_STRING;
            if (is_array($tokens[$index]) && $tokens[$index][0] === $tokenType) {
                $namespace .= '\\' . $tokens[$index][1];
            }
        }
        return ltrim($namespace, '\\');
    }

    private function containsTypeDefinitions($tokens, $startIndex)
    {
        for ($index = $startIndex; isset($tokens[$index]); $index++) {
            if (is_array($tokens[$index]) &&
                ($tokens[$index][0] === T_EXTENDS || $tokens[$index][0] === T_IMPLEMENTS)) {
                return true;
            }
        }
        return false;
    }

    private function getTypeNames($className)
    {
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
