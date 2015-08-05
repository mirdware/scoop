<?php
namespace App\Repository;

\Scoop\IoC\Injector::bind('App\Repository\Message', 'App\Repository\MessageArray');

Interface Message
{
    public function publish();
}
