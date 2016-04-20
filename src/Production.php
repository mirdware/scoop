<?php
namespace App;

class Production extends \Scoop\Bootstrap\Environment
{
    public function __construct()
    {
        parent::__construct('app/config');
        $this->bindInterfaces()
            ->configure();
    }

    private function bindInterfaces()
    {
        return $this->bind('App\Repository\Quote', 'App\Repository\QuoteArray');
    }

    private static function configure()
    {
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
    }
}
