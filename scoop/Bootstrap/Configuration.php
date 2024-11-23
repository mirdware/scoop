<?php

namespace Scoop\Bootstrap;

class Configuration
{
    private $environment;

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
        \Scoop\Http\Exception\Manager::setMessages(
            $this->environment->getConfig("messages.$language.errors", array())
        );
        \Scoop\View\Helper::setKeyMessages("messages.$language.messages." );
    }

    public function setUp()
    {
        $this->setLanguage(
            $this->environment->getConfig('language', 'es')
        );
        \Scoop\View::registerComponents(
            $this->environment->getConfig('components', array())
        );
    }
}
