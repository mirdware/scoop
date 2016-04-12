<?php
namespace App;

class Production extends \Scoop\Bootstrap\Environment
{
    public function __construct()
    {
        parent::__construct('app/config');
        self::configure();
        $this->registerServices()
             ->bindInterfaces()
             ->injectParameters();
    }

    private function bindInterfaces()
    {
        return $this
                ->bind('App\Repository\Quote', 'App\Repository\QuoteArray');
    }

    private function registerServices()
    {
        return $this
                ->registerService('config', $this);
    }

    private function injectParameters()
    {
        \Scoop\View\Helper::setAssets($this->get('assets'));
        \Scoop\Validator::setMessages($this->get('messages.error'));
    }

    private static function configure()
    {
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
    }
}
