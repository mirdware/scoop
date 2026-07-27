<?php

namespace Scoop\Bootstrap;

class Configuration
{
    protected $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function setLanguage($language)
    {
        \Scoop\Validator::setMessages(
            $this->environment->getConfig("messages.$language.failures", array()),
            $this->environment->getConfig("messages.$language.fields", array())
        );
        \Scoop\Http\Error\Mapper::setMessages(
            $this->environment->getConfig("messages.$language.errors", array())
        );
        \Scoop\View\Helper::setKeyMessages("messages.$language.");
    }

    public function setUp()
    {
        $this->setLanguage(
            $this->environment->getConfig('language', 'es')
        );
        \Scoop\View\Template::setPath(
            'app/views/',
            $this->environment->getStoragePath('cache/views')
        );
    }
}
