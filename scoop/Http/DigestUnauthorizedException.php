<?php
namespace Scoop\Http;

class DigestUnauthorizedException extends UnauthorizedException
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        parent::__construct($message, $previous);
        $domain = \Scoop\Context::getService('config')->get('app.name');
        $this->addHeader('WWW-Authenticate: Digest realm="'.$domain.
        '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($domain).'"');
    }
}
