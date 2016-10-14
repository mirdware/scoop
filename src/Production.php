<?php
namespace App;

class Production extends \Scoop\Bootstrap\Environment
{
    public function __construct()
    {
        parent::__construct('app/config');
        $this->bind('app/config/interfaces')
            ->configure();
    }

    private static function configure()
    {
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
    }
}
