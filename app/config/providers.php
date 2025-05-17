<?php

return array(
    'Scoop\Cache' => 'Scoop\Cache\Factory\Simple:create',
    'Scoop\Event\Bus' => 'Scoop\Event\Factory\Bus:create',
    'Scoop\Log\Logger' => 'Scoop\Log\Factory\Logger:create',
    'Scoop\Command\Bus' => 'Scoop\Command\Factory\Bus:create',
    'Scoop\Cache\Item\Pool' => 'Scoop\Cache\Factory\ItemPool:create',
    'Scoop\Command\Writer' => 'Scoop\Command\Factory\Writer:create',
    'Scoop\Persistence\Vault' => 'Scoop\Persistence\Factory\Vault:create',
    'Scoop\Persistence\Entity\Manager' => 'Scoop\Persistence\Factory\EntityManager:create',

    'App\Repository\Quote' => 'App\Repository\QuoteArray'
);
