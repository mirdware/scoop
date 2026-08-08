<?php

namespace Scoop\Bootstrap\Scanner\Source;

class Parser
{
    public function parse($tokens)
    {
        $namespace = '';
        $imports = array();
        $depth = 0;
        $namespaceDepth = 0;
        foreach ($tokens as $index => $token) {
            if ($this->isToken($token, T_NAMESPACE) && $depth === $namespaceDepth) {
                $namespace = $this->getNamespace($index, $tokens);
                if ($this->namespaceUsesBraces($index, $tokens)) {
                    $namespaceDepth = $depth + 1;
                }
            } elseif ($this->isToken($token, T_USE) && $depth === $namespaceDepth) {
                $imports += $this->getImports($index, $tokens);
            } elseif ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
                if ($depth < $namespaceDepth) {
                    $namespaceDepth = $depth;
                    $namespace = '';
                    $imports = array();
                }
            } elseif ($depth === $namespaceDepth && $this->isDeclaration($index, $tokens)) {
                return $this->getClass($index, $tokens, $namespace, $imports);
            }
        }
        return false;
    }
    private function getNamespace($startIndex, $tokens)
    {
        $namespace = '';
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            if ($tokens[$index] === ';' || $tokens[$index] === '{') {
                return ltrim($namespace, '\\');
            }
            if ($this->isName($tokens[$index])) {
                $namespace .= $this->text($tokens[$index]);
            }
        }
        return '';
    }

    private function namespaceUsesBraces($startIndex, $tokens)
    {
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            if ($tokens[$index] === '{') {
                return true;
            }
            if ($tokens[$index] === ';') {
                return false;
            }
        }
        return false;
    }

    private function getImports($startIndex, $tokens)
    {
        $imports = array();
        $name = '';
        $alias = '';
        $hasAlias = false;
        $prefix = '';
        $firstIndex = $this->nextSignificant($startIndex + 1, $tokens);
        if (!$this->isTypeImport($firstIndex, $tokens)) {
            return array();
        }
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if ($token === ';' || $token === ',') {
                $this->addImport($imports, $prefix . $name, $alias, $hasAlias);
                if ($token === ';') {
                    return $imports;
                }
                $name = '';
                $alias = '';
                $hasAlias = false;
            } else {
                $this->readImportToken($token, $name, $alias, $hasAlias, $prefix);
            }
        }
        return array();
    }

    private function addImport(&$imports, $name, $alias, $hasAlias)
    {
        if (!$name) {
            return;
        }
        $normalized = ltrim($name, '\\');
        $key = $hasAlias ? $alias : substr($normalized, strrpos('\\' . $normalized, '\\'));
        $imports[$key] = $normalized;
    }

    private function readImportToken($token, &$name, &$alias, &$hasAlias, &$prefix)
    {
        if ($token === '{') {
            $prefix = rtrim($name, '\\') . '\\';
            $name = '';
        } elseif ($this->isToken($token, T_AS)) {
            $hasAlias = true;
        } elseif ($this->isName($token)) {
            if ($hasAlias) {
                $alias .= $this->text($token);
            } else {
                $name .= $this->text($token);
            }
        }
    }

    private function getClass($startIndex, $tokens, $namespace, $imports)
    {
        $kind = strtolower($this->text($tokens[$startIndex]));
        $nameIndex = $this->nextSignificant($startIndex + 1, $tokens);
        if ($nameIndex === false || !$this->isName($tokens[$nameIndex])) {
            return false;
        }
        $name = ($namespace ? $namespace . '\\' : '') . $this->text($tokens[$nameIndex]);
        $metadata = array(
            'name' => $name,
            'instantiable' => $kind === 'class' && !$this->hasModifier($startIndex, $tokens, T_ABSTRACT),
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
                return $this->getConstructor($index, $tokens, $metadata, $namespace, $imports);
            }
            $this->readClassType($token, $kind, $mode, $metadata, $namespace, $imports);
        }
        return false;
    }

    private function readClassType($token, $kind, &$mode, &$metadata, $namespace, $imports)
    {
        if ($this->isToken($token, T_EXTENDS)) {
            $mode = $kind === 'class' ? 'parent' : 'interface';
            return;
        }
        if ($this->isToken($token, T_IMPLEMENTS)) {
            $mode = 'interface';
            return;
        }
        if ($token === ',' && $mode === 'parent') {
            $mode = null;
            return;
        }
        if (!$mode || !$this->isName($token)) {
            return;
        }
        $typeName = $this->resolveName($this->text($token), $namespace, $imports, $metadata['name'], null);
        if ($mode === 'parent') {
            $metadata['parent'] = $typeName;
            $mode = null;
        } else {
            $metadata['types'][] = $typeName;
        }
    }

    private function getConstructor($startIndex, $tokens, $metadata, $namespace, $imports)
    {
        $depth = 1;
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
                if ($depth === 0) {
                    return $this->complete($metadata);
                }
            } elseif ($depth === 1 && $this->isToken($token, T_FUNCTION)) {
                $nameIndex = $this->nextSignificant($index + 1, $tokens);
                if ($nameIndex !== false && $tokens[$nameIndex] === '&') {
                    $nameIndex = $this->nextSignificant($nameIndex + 1, $tokens);
                }
                if ($nameIndex !== false && strtolower($this->text($tokens[$nameIndex])) === '__construct') {
                    if (!$this->isPublicConstructor($index, $tokens)) {
                        $metadata['instantiable'] = false;
                        $metadata['providers'] = false;
                        return $this->complete($metadata);
                    }
                    return $this->getParameters($nameIndex, $tokens, $metadata, $namespace, $imports);
                }
            }
        }
        return false;
    }

    private function getParameters($nameIndex, $tokens, $metadata, $namespace, $imports)
    {
        $openIndex = $this->nextSignificant($nameIndex + 1, $tokens);
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
                (defined('T_ATTRIBUTE') && $this->isToken($token, T_ATTRIBUTE))
            ) {
                $depth++;
            } elseif ($token === ')' || $token === ']' || $token === '}') {
                $depth--;
                if ($depth === 0) {
                    if ($parameter) {
                        $parameters[] = $parameter;
                    }
                    $metadata['providers'] = $this->resolveParameters($parameters, $namespace, $imports, $metadata);
                    return $this->complete($metadata);
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

    private function complete($metadata)
    {
        if (empty($metadata['types'])) {
            unset($metadata['types']);
        }
        return $metadata;
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
                $provider = $this->resolveName(
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
            if (defined('T_ATTRIBUTE') && $this->isToken($token, T_ATTRIBUTE)) {
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
            if ($this->isIgnorable($token) || $token === '&' || $token === '?' || $this->isVisibility($token)) {
                continue;
            }
            if (!$this->isName($token)) {
                return null;
            }
            $name .= $this->text($token);
        }
        if (!$name || $this->isBuiltin($name) || strpos($name, '|') !== false || strpos($name, '&') !== false) {
            return null;
        }
        return $name;
    }

    private function resolveName($name, $namespace, $imports, $className, $parentName)
    {
        $absolute = isset($name[0]) && $name[0] === '\\';
        $name = ltrim($name, '\\');
        if (strtolower($name) === 'self' || strtolower($name) === 'static') {
            return $className;
        }
        if (strtolower($name) === 'parent') {
            return $parentName;
        }
        if ($absolute) {
            return $name;
        }
        if (strpos(strtolower($name), 'namespace\\') === 0) {
            return $namespace . '\\' . substr($name, 10);
        }
        $separator = strpos($name, '\\');
        $alias = $separator === false ? $name : substr($name, 0, $separator);
        if (isset($imports[$alias])) {
            return $imports[$alias] . ($separator === false ? '' : substr($name, $separator));
        }
        return $namespace ? $namespace . '\\' . $name : $name;
    }

    private function hasModifier($startIndex, $tokens, $modifier)
    {
        for ($index = $startIndex - 1; $index >= 0; $index--) {
            if ($this->isIgnorable($tokens[$index])) {
                continue;
            }
            return $this->isToken($tokens[$index], $modifier);
        }
        return false;
    }

    private function findToken($tokens, $type)
    {
        foreach ($tokens as $index => $token) {
            if ($this->isToken($token, $type)) {
                return $index;
            }
        }
        return false;
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

    private function isPublicConstructor($startIndex, $tokens)
    {
        for ($index = $startIndex - 1; $index >= 0; $index--) {
            $token = $tokens[$index];
            if ($token === '{' || $token === '}' || $token === ';') {
                return true;
            }
            if ($this->isToken($token, T_PRIVATE) || $this->isToken($token, T_PROTECTED)) {
                return false;
            }
        }
        return true;
    }

    private function isVisibility($token)
    {
        if (
            $this->isToken($token, T_PUBLIC) || $this->isToken($token, T_PROTECTED) ||
            $this->isToken($token, T_PRIVATE)
        ) {
            return true;
        }
        return defined('T_READONLY') && $this->isToken($token, T_READONLY);
    }

    private function isBuiltin($name)
    {
        return in_array(strtolower(ltrim($name, '\\')), array(
            'array', 'bool', 'boolean', 'callable', 'float', 'double', 'int', 'integer',
            'iterable', 'mixed', 'null', 'object', 'resource', 'string', 'false', 'true', 'void', 'never'
        ), true);
    }

    private function nextSignificant($startIndex, $tokens)
    {
        for ($index = $startIndex; isset($tokens[$index]); $index++) {
            if (!$this->isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return false;
    }

    private function previousSignificant($startIndex, $tokens)
    {
        for ($index = $startIndex; $index >= 0; $index--) {
            if (!$this->isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return false;
    }

    private function isTypeImport($index, $tokens)
    {
        if ($index === false) {
            return true;
        }
        return !$this->isToken($tokens[$index], T_FUNCTION) && !$this->isToken($tokens[$index], T_CONST);
    }

    private function isDeclaration($index, $tokens)
    {
        $token = $tokens[$index];
        if (
            !$this->isToken($token, T_CLASS) && !$this->isToken($token, T_INTERFACE) &&
            !(defined('T_TRAIT') && $this->isToken($token, T_TRAIT))
        ) {
            return false;
        }
        $previous = $this->previousSignificant($index - 1, $tokens);
        return $previous === false ||
            (!$this->isToken($tokens[$previous], T_DOUBLE_COLON) && !$this->isToken($tokens[$previous], T_NEW));
    }

    private function isName($token)
    {
        if (!is_array($token)) {
            return $token === '\\';
        }
        $names = array(T_STRING);
        foreach (array('T_NS_SEPARATOR', 'T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED', 'T_NAME_RELATIVE') as $name) {
            if (defined($name)) {
                $names[] = constant($name);
            }
        }
        return in_array($token[0], $names, true);
    }

    private function isIgnorable($token)
    {
        return is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true);
    }

    private function isToken($token, $type)
    {
        return is_array($token) && $token[0] === $type;
    }

    private function text($token)
    {
        return is_array($token) ? $token[1] : $token;
    }
}
