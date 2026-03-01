<?php

namespace Scoop\Security\Cipher;

abstract final class CbcAlgorithm
{
    public static function encrypt($string, $secret)
    {
        $salt = openssl_random_pseudo_bytes(16);
        $key = hash_hmac('sha256', $secret, $salt, true);
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivSize);
        $ciphertext = openssl_encrypt($string, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $salt . $iv . $ciphertext, $key, true);
        return $salt . $iv . $hmac . $ciphertext;
    }

    public static function decrypt($string, $secret)
    {
        $salt = substr($string, 0, 16);
        $key = hash_hmac('sha256', $secret, $salt, true);
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($string, 16, $ivSize);
        $hmac = substr($string, 16 + $ivSize, 32);
        $ciphertext = substr($string, 16 + $ivSize + 32);
        $calculatedHmac = hash_hmac('sha256', $salt . $iv . $ciphertext, $key, true);
        if (!self::hashEquals($hmac, $calculatedHmac)) {
            return false;
        }
        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }

    private static function hashEquals($a, $b)
    {
        if (strlen($a) !== strlen($b)) return false;
        $res = 0;
        for ($i = 0; $i < strlen($a); $i++) $res |= ord($a[$i]) ^ ord($b[$i]);
        return $res === 0;
    }
}
