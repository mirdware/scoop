<?php

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

return array(
    'default' => array(
        'host' => $host ? $host : 'db',
        'database' => 'scoop',
        'user' => $user ? $user : 'postgres',
        'password' => $password ? $password : 'postgres'
    )
);
