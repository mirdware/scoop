<?php

namespace Scoop\Persistence\Factory;

class Vault
{
    public function create()
    {
        $secret = \Scoop\Context::getEnvironment()->getConfig('vault', 'aRVIKNStQR9Lr56');
        $encoding = 'base64';
        if (is_array($secret)) {
            $encoding = $secret['encoding'];
            $secret = $secret['secret'];
        }
        return new \Scoop\Persistence\Vault($secret, $encoding);
    }
}
