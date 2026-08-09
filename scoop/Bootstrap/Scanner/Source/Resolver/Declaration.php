<?php

namespace Scoop\Bootstrap\Scanner\Source\Resolver;

class Declaration
{
    private $imports;

    public function __construct(Importer $imports)
    {
        $this->imports = $imports;
    }

    public function isDeclaration($index, $tokens)
    {
        $token = $tokens[$index];
        if (
            !Token::isToken($token, T_CLASS) && !Token::isToken($token, T_INTERFACE) &&
            !(defined('T_TRAIT') && Token::isToken($token, T_TRAIT))
        ) {
            return false;
        }
        $previous = Token::previousSignificant($index - 1, $tokens);
        return $previous === false ||
            (!Token::isToken($tokens[$previous], T_DOUBLE_COLON) && !Token::isToken($tokens[$previous], T_NEW));
    }

    public function hasModifier($startIndex, $tokens, $modifier)
    {
        for ($index = $startIndex - 1; $index >= 0; $index--) {
            if (Token::isIgnorable($tokens[$index])) {
                continue;
            }
            return Token::isToken($tokens[$index], $modifier);
        }
        return false;
    }

    public function readType($token, $kind, &$mode, &$metadata, $namespace, $imports)
    {
        if (Token::isToken($token, T_EXTENDS)) {
            $mode = $kind === 'class' ? 'parent' : 'interface';
            return;
        }
        if (Token::isToken($token, T_IMPLEMENTS)) {
            $mode = 'interface';
            return;
        }
        if ($token === ',' && $mode === 'parent') {
            $mode = null;
            return;
        }
        if (!$mode || !Token::isName($token)) {
            return;
        }
        $typeName = $this->imports->resolveName(Token::text($token), $namespace, $imports, $metadata['name'], null);
        if ($mode === 'parent') {
            $metadata['parent'] = $typeName;
            $mode = null;
        } else {
            $metadata['types'][] = $typeName;
        }
    }
}
