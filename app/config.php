<?php
return array(
    'app' => 'json:package',
    'db' => array(
        'default' => array(
            'database' => 'scoop',
            'user' => 'postgres',
            'password' => 'postgres'
        )
    ),
    'messages' => require 'config/messages.php',
    'routes' => require 'config/routes.php',
    'providers' => require 'config/providers.php'
);
