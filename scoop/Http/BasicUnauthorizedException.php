<?php
namespace Scoop\Http;

class BasicUnauthorizedException extends UnauthorizedException
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        parent::__construct($message, $previous);
        $domain = \Scoop\Context::getService('config')->get('app.name');
        $this->addHeader('WWW-Authenticate: Basic realm="'.$domain.'"');
    }
}
