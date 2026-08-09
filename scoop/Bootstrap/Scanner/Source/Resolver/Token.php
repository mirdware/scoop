<?php

namespace Scoop\Bootstrap\Scanner\Source\Resolver;

final class Token
{
    public static function isToken($token, $type)
    {
        return is_array($token) && $token[0] === $type;
    }

    public static function isName($token)
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

    public static function isIgnorable($token)
    {
        return is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true);
    }

    public static function text($token)
    {
        return is_array($token) ? $token[1] : $token;
    }

    public static function nextSignificant($startIndex, $tokens)
    {
        for ($index = $startIndex; isset($tokens[$index]); $index++) {
            if (!self::isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return false;
    }

    public static function previousSignificant($startIndex, $tokens)
    {
        for ($index = $startIndex; $index >= 0; $index--) {
            if (!self::isIgnorable($tokens[$index])) {
                return $index;
            }
        }
        return false;
    }
}
