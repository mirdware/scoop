<?php
namespace Environment;

class Production extends \Scoop\Bootstrap\Environment
{
    public function configure()
    {
        $router = new \Scoop\IoC\Router();
        $router->register('app/routes');
        $config = new \Scoop\Bootstrap\Configuration();
        $config->add('app/config');
        $this->setRouter($router)
             ->registerService('config', $config)
             ->bind('App\Repository\Quote', 'App\Repository\QuoteArray');
    }
}
