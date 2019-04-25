<?php
namespace Scoop\Http;

class DigestUnauthorizedException extends UnauthorizedException
{
    private $domain;

    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        $this->domain = \Scoop\Context::getService('config')->get('app.name');
        parent::__construct($message, $previous);
        $this->headers[] = 'WWW-Authenticate: Digest realm="'.$this->domain.
        '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($this->domain).'"';
    }
}
