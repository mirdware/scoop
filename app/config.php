<?php

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

return array(
    'app' => 'json:package',
    'db' => array(
        'default' => array(
            'host' => $host ? $host : 'db',
            'database' => 'scoop',
            'user' => $user ? $user : 'postgres',
            'password' => $password ? $password : 'postgres'
        )
    ),
    'messages' => array(
        'es' => 'import:app/config/lang/es',
        'en' => 'import:app/config/lang/en'
    ),
    'routes' => require 'config/routes.php',
    'providers' => require 'config/providers.php'
);
