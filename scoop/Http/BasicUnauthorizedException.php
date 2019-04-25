<?php
namespace Scoop\Http;

class BasicUnauthorizedException extends UnauthorizedException
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        $domain = \Scoop\Context::getService('config')->get('app.name');
        parent::__construct($message, $previous);
        $this->headers[] = 'WWW-Authenticate: Basic realm="'.$domain.'"';
    }
}
