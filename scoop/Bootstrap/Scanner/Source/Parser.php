<?php

namespace Scoop\Bootstrap\Scanner\Source;

class Parser
{
    private $importer;
    private $declaration;
    private $constructor;

    public function __construct()
    {
        $this->importer = new Resolver\Importer();
        $this->declaration = new Resolver\Declaration($this->importer);
        $this->constructor = new Resolver\Constructor($this->importer);
    }

    public function parse($tokens)
    {
        $namespace = '';
        $imports = array();
        $depth = 0;
        $namespaceDepth = 0;
        foreach ($tokens as $index => $token) {
            if (Resolver\Token::isToken($token, T_NAMESPACE) && $depth === $namespaceDepth) {
                list($namespace, $usesBraces) = $this->importer->resolveNamespace($index, $tokens);
                if ($usesBraces) {
                    $namespaceDepth = $depth + 1;
                }
            } elseif (Resolver\Token::isToken($token, T_USE) && $depth === $namespaceDepth) {
                $imports += $this->importer->resolveImports($index, $tokens);
            } elseif ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
                if ($depth < $namespaceDepth) {
                    $namespaceDepth = $depth;
                    $namespace = '';
                    $imports = array();
                }
            } elseif ($depth === $namespaceDepth && $this->declaration->isDeclaration($index, $tokens)) {
                return $this->getClass($index, $tokens, $namespace, $imports);
            }
        }
        return false;
    }

    private function getClass($startIndex, $tokens, $namespace, $imports)
    {
        $kind = strtolower(Resolver\Token::text($tokens[$startIndex]));
        $nameIndex = Resolver\Token::nextSignificant($startIndex + 1, $tokens);
        if ($nameIndex === false || !Resolver\Token::isName($tokens[$nameIndex])) {
            return false;
        }
        $name = ($namespace ? $namespace . '\\' : '') . Resolver\Token::text($tokens[$nameIndex]);
        $metadata = array(
            'name' => $name,
            'instantiable' => $kind === 'class' && !$this->declaration->hasModifier($startIndex, $tokens, T_ABSTRACT),
            'types' => array()
        );
        $mode = null;
        for ($index = $nameIndex + 1; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if ($token === '{') {
                if ($kind === 'trait') {
                    return array();
                }
                if ($kind !== 'class') {
                    return $this->complete($metadata);
                }
                $result = $this->constructor->resolve($index, $tokens, $metadata, $namespace, $imports);
                return $result === false ? false : $this->complete($result);
            }
            $this->declaration->readType($token, $kind, $mode, $metadata, $namespace, $imports);
        }
        return false;
    }

    private function complete($metadata)
    {
        if (empty($metadata['types'])) {
            unset($metadata['types']);
        }
        return $metadata;
    }
}
