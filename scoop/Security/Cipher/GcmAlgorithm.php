<?php

namespace Scoop\Security\Cipher;

abstract final class GcmAlgorithm
{
    public static function encrypt($string, $secret)
    {
        $keysalt = openssl_random_pseudo_bytes(16);
        $key = hash_pbkdf2('sha512', $secret, $keysalt, 20000, 32, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $encryptedstring = openssl_encrypt($string, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        return $keysalt . $iv . $encryptedstring . $tag;
    }

    public static function decrypt($string, $secret)
    {
        $keysalt = substr($string, 0, 16);
        $key = hash_pbkdf2('sha512', $secret, $keysalt, 20000, 32, true);
        $ivlength = openssl_cipher_iv_length('aes-256-gcm');
        $iv = substr($string, 16, $ivlength);
        $tag = substr($string, -16);
        return openssl_decrypt(substr($string, 16 + $ivlength, -16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
