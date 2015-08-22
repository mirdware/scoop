<?php
namespace Environment;

class Production extends \Scoop\Bootstrap\Environment
{
    public function configure()
    {
        $router = new \Scoop\IoC\Router();
        $router->register('app/routes');
        $this->setRouter($router)
             ->registerService('config', function ()
             {
                 $config = new \Scoop\Bootstrap\Config();
                 $config->add('app/config');
                 return $config;
             })
             ->bind('App\Repository\Quote', 'App\Repository\QuoteArray');
    }
}
