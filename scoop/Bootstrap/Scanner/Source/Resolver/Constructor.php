<?php

namespace Scoop\Bootstrap\Scanner\Source\Resolver;

class Constructor
{
    private $imports;

    public function __construct(Importer $imports)
    {
        $this->imports = $imports;
    }

    public function resolve($startIndex, $tokens, $metadata, $namespace, $imports)
    {
        $depth = 1;
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
                if ($depth === 0) {
                    return $metadata;
                }
            } elseif ($depth === 1 && Token::isToken($token, T_FUNCTION)) {
                $nameIndex = Token::nextSignificant($index + 1, $tokens);
                if ($nameIndex !== false && $tokens[$nameIndex] === '&') {
                    $nameIndex = Token::nextSignificant($nameIndex + 1, $tokens);
                }
                if ($nameIndex !== false && strtolower(Token::text($tokens[$nameIndex])) === '__construct') {
                    if (!$this->isPublicConstructor($index, $tokens)) {
                        $metadata['instantiable'] = false;
                        $metadata['providers'] = false;
                        return $metadata;
                    }
                    return $this->resolveParametersFrom($nameIndex, $tokens, $metadata, $namespace, $imports);
                }
            }
        }
        return false;
    }

    private function resolveParametersFrom($nameIndex, $tokens, $metadata, $namespace, $imports)
    {
        $openIndex = Token::nextSignificant($nameIndex + 1, $tokens);
        if ($openIndex === false || $tokens[$openIndex] !== '(') {
            return false;
        }
        $parameters = array();
        $parameter = array();
        $depth = 1;
        for ($index = $openIndex + 1; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if (
                $token === '(' || $token === '[' || $token === '{' ||
                (defined('T_ATTRIBUTE') && Token::isToken($token, T_ATTRIBUTE))
            ) {
                $depth++;
            } elseif ($token === ')' || $token === ']' || $token === '}') {
                $depth--;
                if ($depth === 0) {
                    if ($parameter) {
                        $parameters[] = $parameter;
                    }
                    $metadata['providers'] = $this->resolveParameters($parameters, $namespace, $imports, $metadata);
                    return $metadata;
                }
            }
            if ($depth === 1 && $token === ',') {
                $parameters[] = $parameter;
                $parameter = array();
            } else {
                $parameter[] = $token;
            }
        }
        return false;
    }

    private function resolveParameters($parameters, $namespace, $imports, $metadata)
    {
        $providers = array();
        $usesDefault = false;
        foreach ($parameters as $parameter) {
            $variableIndex = $this->findToken($parameter, T_VARIABLE);
            if ($variableIndex === false) {
                return false;
            }
            $hasDefault = $this->contains($parameter, '=');
            $provider = $this->getParameterType(array_slice($parameter, 0, $variableIndex));
            if ($provider !== null) {
                $provider = $this->imports->resolveName(
                    $provider,
                    $namespace,
                    $imports,
                    $metadata['name'],
                    isset($metadata['parent']) ? $metadata['parent'] : null
                );
                if ($usesDefault) {
                    return false;
                }
                $providers[] = $provider;
            } elseif ($hasDefault) {
                $usesDefault = true;
            } else {
                return false;
            }
        }
        return $providers;
    }

    private function getParameterType($tokens)
    {
        $name = '';
        $attributeDepth = 0;
        foreach ($tokens as $token) {
            if (defined('T_ATTRIBUTE') && Token::isToken($token, T_ATTRIBUTE)) {
                $attributeDepth = 1;
                continue;
            }
            if ($attributeDepth) {
                if ($token === '[') {
                    $attributeDepth++;
                }
                if ($token === ']') {
                    $attributeDepth--;
                }
                continue;
            }
            if (Token::isIgnorable($token) || $token === '&' || $token === '?' || $this->isVisibility($token)) {
                continue;
            }
            if (!Token::isName($token)) {
                return null;
            }
            $name .= Token::text($token);
        }
        if (!$name || $this->isBuiltin($name) || strpos($name, '|') !== false || strpos($name, '&') !== false) {
            return null;
        }
        return $name;
    }

    private function isPublicConstructor($startIndex, $tokens)
    {
        for ($index = $startIndex - 1; $index >= 0; $index--) {
            $token = $tokens[$index];
            if ($token === '{' || $token === '}' || $token === ';') {
                return true;
            }
            if (Token::isToken($token, T_PRIVATE) || Token::isToken($token, T_PROTECTED)) {
                return false;
            }
        }
        return true;
    }

    private function isVisibility($token)
    {
        if (
            Token::isToken($token, T_PUBLIC) || Token::isToken($token, T_PROTECTED) ||
            Token::isToken($token, T_PRIVATE)
        ) {
            return true;
        }
        return defined('T_READONLY') && Token::isToken($token, T_READONLY);
    }

    private function isBuiltin($name)
    {
        return in_array(strtolower(ltrim($name, '\\')), array(
            'array', 'bool', 'boolean', 'callable', 'float', 'double', 'int', 'integer',
            'iterable', 'mixed', 'null', 'object', 'resource', 'string', 'false', 'true', 'void', 'never'
        ), true);
    }

    private function contains($tokens, $value)
    {
        foreach ($tokens as $token) {
            if ($token === $value) {
                return true;
            }
        }
        return false;
    }

    private function findToken($tokens, $type)
    {
        foreach ($tokens as $index => $token) {
            if (Token::isToken($token, $type)) {
                return $index;
            }
        }
        return false;
    }
}
