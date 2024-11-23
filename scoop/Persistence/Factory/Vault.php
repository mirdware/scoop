<?php

namespace Scoop\Persistence\Factory;

class Vault
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        $secret = $this->environment->getConfig('vault', 'aRVIKNStQR9Lr56');
        $encoding = 'base64';
        if (is_array($secret)) {
            $encoding = $secret['encoding'];
            $secret = $secret['secret'];
        }
        return new \Scoop\Persistence\Vault($secret, $encoding);
    }
}
