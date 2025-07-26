<?php

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$db = getenv('DB_NAME');

return array(
    'default' => array(
        'host' => $host ? $host : 'db',
        'database' => $db ? $db : 'scoop',
        'user' => $user ? $user : 'postgres',
        'password' => $password ? $password : 'postgres'
    )
);
