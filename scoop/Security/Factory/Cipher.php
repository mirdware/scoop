<?php

namespace Scoop\Security\Factory;

class Cipher
{
    private $context;

    public function __construct(\Scoop\Context $context)
    {
        $this->context = $context;
    }

    public function create()
    {
        $secret = $this->context->getConfig('cipher', 'bVZi0dt8aN4piLCgOvA4sCYE2Zw16uH3');
        $encoding = 'base64';
        if (is_array($secret)) {
            $encoding = $secret['encoding'];
            $secret = $secret['secret'];
        }
        return new \Scoop\Security\Cipher($secret, $encoding);
    }
}
