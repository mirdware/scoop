<?php
return array(
    'app' => json_decode(file_get_contents(
        __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'package.json'
    ), true),
    'asset' => array(
        'path' => 'public/',
        'css' => 'css/',
        'js' => 'js/',
        'img' => 'images/'
    ),
    'db' => array(
        'default' => array(
            'database' => 'postgres',
            'user' => 'postgres',
            'password' => 'postgres',
            'host' => 'localhost',
            'driver' => 'pgsql'
        )
    )
);
