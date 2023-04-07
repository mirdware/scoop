<?php

namespace Scoop\Http;

/**
 * @deprecated 7.1
 */
class BasicUnauthorizedException extends UnauthorizedException
{
    public function __construct($message = 'Not authorized', \Exception $previous = null)
    {
        parent::__construct($message, $previous);
        $domain = \Scoop\Context::getEnvironment()->getConfig('app.name');
        $this->addHeader('WWW-Authenticate: Basic realm="' . $domain . '"');
    }
}
