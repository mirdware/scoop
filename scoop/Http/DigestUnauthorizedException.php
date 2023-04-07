<?php

namespace Scoop\Http;

/**
 * @deprecated
 */
class DigestUnauthorizedException extends UnauthorizedException
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        parent::__construct($message, $previous);
        $domain = \Scoop\Context::getEnvironment()->getConfig('app.name');
        $this->addHeader('WWW-Authenticate: Digest realm="' . $domain .
        '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($domain) . '"');
    }
}
