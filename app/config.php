<?php
return array(
    'app' => json_decode(file_get_contents(
        __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'package.json'
    ), true),
    'db' => array(
        'default' => array(
            'database' => 'scoop',
            'user' => 'postgres',
            'password' => 'postgres',
            'host' => 'localhost',
            'driver' => 'pgsql'
        )
    ),
    'messages' => require 'config/messages.php',
    'routes' => require 'config/routes.php',
    'providers' => require 'config/providers.php',
    'services' => require 'config/services.php',
    'components' => require 'config/components.php'
);
