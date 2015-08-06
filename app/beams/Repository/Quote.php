<?php
namespace App\Repository;

\Scoop\IoC\Injector::bind('App\Repository\Quote', 'App\Repository\QuoteArray');

Interface Quote
{
    public function publish();
}
