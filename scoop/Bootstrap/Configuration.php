<?php

namespace Scoop\Bootstrap;

class Configuration
{
    protected $context;

    public function __construct(\Scoop\Bootstrap\Environment $context)
    {
        $this->context = $context;
    }

    public function setLanguage($language)
    {
        \Scoop\Validator::setMessages(
            $this->context->getConfig("messages.$language.failures", array()),
            $this->context->getConfig("messages.$language.fields", array())
        );
        \Scoop\Http\Error\Mapper::setMessages(
            $this->context->getConfig("messages.$language.errors", array())
        );
        \Scoop\View\Helper::setKeyMessages("messages.$language.");
    }

    public function setUp()
    {
        $this->setLanguage(
            $this->context->getConfig('language', 'es')
        );
        \Scoop\View\Template::setPath(
            'app/views/',
            $this->context->getStoragePath('cache/views')
        );
    }
}
