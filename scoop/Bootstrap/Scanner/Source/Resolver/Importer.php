<?php

namespace Scoop\Bootstrap\Scanner\Source\Resolver;

class Importer
{
    public function resolveNamespace($startIndex, $tokens)
    {
        $namespace = '';
        for ($index = $startIndex + 1; isset($tokens[$index]); $index++) {
            if ($tokens[$index] === '{') {
                return array(ltrim($namespace, '\\'), true);
            }
            if ($tokens[$index] === ';') {
                return array(ltrim($namespace, '\\'), false);
            }
            if (Token::isName($tokens[$index])) {
                $namespace .= Token::text($tokens[$index]);
            }
        }
        return array('', false);
    }

    public function resolveImports($startIndex, $tokens)
    {
        $imports = array();
        $name = '';
        $alias = '';
        $hasAlias = false;
        $prefix = '';
        $firstIndex = Token::nextSignificant($startIndex + 1, $tokens);
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

    public function resolveName($name, $namespace, $imports, $className, $parentName)
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
        } elseif (Token::isToken($token, T_AS)) {
            $hasAlias = true;
        } elseif (Token::isName($token)) {
            if ($hasAlias) {
                $alias .= Token::text($token);
            } else {
                $name .= Token::text($token);
            }
        }
    }

    private function isTypeImport($index, $tokens)
    {
        if ($index === false) {
            return true;
        }
        return !Token::isToken($tokens[$index], T_FUNCTION) && !Token::isToken($tokens[$index], T_CONST);
    }
}
