<?php
namespace Environment;

class Production extends \Scoop\Bootstrap\Environment
{

    public function configure()
    {
        self::defineConstants();
        $this->setRouter(new \Scoop\IoC\Router('app/routes'))
             ->setConfig(new \Scoop\Bootstrap\Configuration('app/config'))
             ->registerServices()
             ->bindInterfaces();
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

    private static function defineConstants()
    {
        define('ROOT', '//'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/');
    }
}
