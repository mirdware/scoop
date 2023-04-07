<?php

namespace Scoop\Storage;

class Crypt
{
    private $secret;
    private $encoding;

    public function __construct($secret, $encoding = 'base64')
    {
        $this->secret = $secret;
        $this->encoding = $encoding;
    }

    public function encrypt($string)
    {
        $keysalt = openssl_random_pseudo_bytes(16);
        $key = hash_pbkdf2('sha512', $this->secret, $keysalt, 20000, 32, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $encryptedstring = openssl_encrypt($string, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($this->encoding === 'base64') {
            return base64_encode($keysalt . $iv . $encryptedstring . $tag);
        }
        if ($this->encoding === 'hex') {
            return bin2hex($keysalt . $iv . $encryptedstring . $tag);
        }
        return $keysalt . $iv . $encryptedstring . $tag;
    }

    public function decrypt($string)
    {
        if ($this->encoding === 'base64') {
            $string = base64_decode($string);
        } elseif ($this->encoding === 'hex') {
            $string = hex2bin($string);
        }
        $keysalt = substr($string, 0, 16);
        $key = hash_pbkdf2('sha512', $this->secret, $keysalt, 20000, 32, true);
        $ivlength = openssl_cipher_iv_length('aes-256-gcm');
        $iv = substr($string, 16, $ivlength);
        $tag = substr($string, -16);
        return openssl_decrypt(substr($string, 16 + $ivlength, -16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
