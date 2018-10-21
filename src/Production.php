<?php
namespace App;

use \Scoop\Bootstrap\Environment;

class Production extends Environment
{
    public function __construct()
    {
        parent::__construct('app/config');
        $this->bind('app/config/providers')
            ->configure();
    }

    private static function configure()
    {
        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'esp');
        date_default_timezone_set('America/Bogota');
    }
}
