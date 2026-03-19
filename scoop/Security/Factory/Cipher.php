<?php

namespace Scoop\Security\Factory;

class Cipher
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        $secret = $this->environment->getConfig('cipher', 'bVZi0dt8aN4piLCgOvA4sCYE2Zw16uH3');
        $encoding = 'base64';
        if (is_array($secret)) {
            $encoding = $secret['encoding'];
            $secret = $secret['secret'];
        }
        return new \Scoop\Security\Cipher($secret, $encoding);
    }
}
