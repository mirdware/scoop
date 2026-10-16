<?php

return array(
    'Scoop\Event\Bus' => array(
        'service' => 'Scoop\Event\Factory\Bus',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Log\Logger' => array(
        'service' => 'Scoop\Log\Factory\Logger',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Command\Bus' => array(
        'service' => 'Scoop\Command\Factory\Bus',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Cache' => array(
        'service' => 'Scoop\Cache\Factory\Simple',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Cache\Item\Pool' => array(
        'service' => 'Scoop\Cache\Factory\ItemPool',
        'method' => 'createInFile',
        'scope' => 'singleton'
    ),
    'Scoop\Command\Writer' => array(
        'service' => 'Scoop\Command\Factory\Writer',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Security\Cipher' => array(
        'service' => 'Scoop\Security\Factory\Cipher',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Persistence\Connection' => array(
        'service' => 'Scoop\Persistence\Factory\Connection',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'Scoop\Persistence\Entity\Manager' => array(
        'service' => 'Scoop\Persistence\Factory\EntityManager',
        'method' => 'create',
        'scope' => 'singleton'
    ),
    'App\Repository\Quote' => 'App\Repository\QuoteArray'
);
