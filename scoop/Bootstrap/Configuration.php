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
        \Scoop\Http\Exception\Manager::setMessages(
            $this->environment->getConfig("messages.$language.errors", array())
        );
        \Scoop\View\Helper::setKeyMessages("messages.$language.messages." );
    }

    public function setStorage($storage)
    {
        \Scoop\Bootstrap\Scanner::setStorage($storage);
        \Scoop\View\Template::setPath('app/views/', "{$storage}cache/views/");
    }

    public function setUp()
    {
        $this->setLanguage(
            $this->environment->getConfig('language', 'es')
        );
        $this->setStorage(
            $this->environment->getConfig('storage', 'app/storage/')
        );
    }
}
