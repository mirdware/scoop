<?php

namespace Scoop\Bootstrap\Scanner;

class Source extends \Scoop\Bootstrap\Scanner
{
    private $parser;
    private $externalTypes = array();
    private $externalProviders = array();

    public function __construct(\Scoop\Bootstrap\Environment $environment, $directory, $prefix)
    {
        $cacheFilePaths = array(
            'types' => $environment->getStoragePath('cache/project') . "{$prefix}types.php",
            'providers' => $environment->getStoragePath('cache/project') . "{$prefix}providers.php"
        );
        $metaFilePath = $environment->getStoragePath('cache/project') . "{$prefix}meta.php";
        parent::__construct($directory, '/\.php$/', $cacheFilePaths, $metaFilePath);
        $this->parser = new Source\Parser();
    }

    protected function build($metaMap)
    {
        $typeMap = array();
        $providerMap = array();
        $declarations = array();
        foreach ($metaMap as $metadata) {
            if (isset($metadata['name'])) {
                $declarations[$metadata['name']] = $metadata;
            }
        }
        foreach ($declarations as $declarationName => $metadata) {
            $providers = $this->getProviders($metadata, $declarations);
            if ($metadata['instantiable'] && $providers !== false) {
                $typeNames = $this->getTypeNames($metadata, $declarations);
                foreach ($typeNames as $typeName) {
                    if (!isset($typeMap[$typeName])) {
                        $typeMap[$typeName] = array();
                    }
                    $typeMap[$typeName][] = $declarationName;
                }
                $providerMap[$declarationName] = $providers;
            }
        }
        return array('types' => $typeMap, 'providers' => $providerMap);
    }

    protected function check($filePath)
    {
        $stream = fopen($filePath, 'r');
        if (!$stream) {
            return array();
        }
        $tokenizer = new Source\Tokenizer($stream);
        while (($tokens = $tokenizer->tokenize()) !== false) {
            $metadata = $this->parser->parse($tokens);
            if ($metadata !== false) {
                fclose($stream);
                return $metadata;
            }
        }
        fclose($stream);
        return array();
    }

    private function getTypeNames($declaration, $declarations, $visited = array())
    {
        if (isset($visited[$declaration['name']])) {
            return array();
        }
        $visited[$declaration['name']] = true;
        $directTypes = isset($declaration['types']) ? $declaration['types'] : array();
        $types = $directTypes;
        if (isset($declaration['parent'])) {
            $types[] = $declaration['parent'];
            if (isset($declarations[$declaration['parent']])) {
                $parentTypes = $this->getTypeNames(
                    $declarations[$declaration['parent']],
                    $declarations,
                    $visited
                );
                $types = array_merge($types, $parentTypes);
            } else {
                $types = array_merge($types, $this->getExternalTypes($declaration['parent']));
            }
        }
        foreach ($directTypes as $typeName) {
            if (isset($declarations[$typeName])) {
                $types = array_merge($types, $this->getTypeNames($declarations[$typeName], $declarations, $visited));
            } else {
                $types = array_merge($types, $this->getExternalTypes($typeName));
            }
        }
        return array_values(array_unique($types));
    }

    private function getProviders($declaration, $declarations, $visited = array())
    {
        if (isset($visited[$declaration['name']])) {
            return false;
        }
        $visited[$declaration['name']] = true;
        if (array_key_exists('providers', $declaration)) {
            return $declaration['providers'];
        }
        if (isset($declaration['parent'])) {
            if (isset($declarations[$declaration['parent']])) {
                return $this->getProviders($declarations[$declaration['parent']], $declarations, $visited);
            }
            return $this->getExternalProviders($declaration['parent']);
        }
        return array();
    }

    private function getExternalTypes($name)
    {
        if (!isset($this->externalTypes[$name])) {
            $this->externalTypes[$name] = array();
            if (class_exists($name) || interface_exists($name)) {
                $class = new \ReflectionClass($name);
                $parents = array();
                $parent = $class->getParentClass();
                while ($parent) {
                    $parents[] = $parent->getName();
                    $parent = $parent->getParentClass();
                }
                $parents = array_merge($parents, $class->getInterfaceNames());
                $this->externalTypes[$name] = array_values(array_unique($parents));
            }
        }
        return $this->externalTypes[$name];
    }

    private function getExternalProviders($name)
    {
        if (!array_key_exists($name, $this->externalProviders)) {
            $inspector = new \Scoop\Container\Inspector($name);
            $this->externalProviders[$name] = $inspector->resolveProviders();
        }
        return $this->externalProviders[$name];
    }
}
