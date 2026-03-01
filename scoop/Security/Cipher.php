<?php

namespace Scoop\Security;

class Cipher
{
    private $secret;
    private $encoding;
    private static $algorithms = array('cbc', 'gcm');

    public function __construct($secret, $encoding)
    {
        $this->secret = $secret;
        $this->encoding = $encoding;
    }

    public function isEncrypted($string)
    {
        return is_string($string) && preg_match('/^[\$#]?\d+:/', $string) === 1;
    }

    public function encrypt($string)
    {
        $version = PHP_VERSION_ID < 70100 ? 0 : 1;
        $string = call_user_func(
            array($this->getClassName($version), 'encrypt'),
            $string, $this->secret
        );
        if ($this->encoding === 'base64') {
            return '$' . $version . ':' . base64_encode($string);
        }
        if ($this->encoding === 'hex') {
            return '#' . $version . ':' . bin2hex($string);
        }
        return "$version:$string";
    }

    public function decrypt($string)
    {
        $version = explode(':', $string, 2);
        if (!isset($version[1])) {
            return false;
        }
        $string = $version[1];
        $version = $version[0];
        if ($version[0] === '$') {
            $string = base64_decode($string);
            $version = substr($version, 1);
        } elseif ($version[0] === '#') {
            $string = hex2bin($string);
            $version = substr($version, 1);
        }
        if (!isset(self::$algorithms[$version])) {
            return false;
        }
        return call_user_func(
            array($this->getClassName($version), 'decrypt'),
            $string, $this->secret
        );
    }

    private function getClassName($version)
    {
        return __NAMESPACE__ . '\\Cipher\\' . ucwords(self::$algorithms[$version]) . 'Algorithm';
    }
}
