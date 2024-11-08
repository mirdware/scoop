<?php

use App\Controller\Home;

return array(
    'home' => array(
        'url' => '/',
        'controller' => Home::class
    ),
    'health-check' => array(
        'url' => '/health',
        'controller' => array(
            'get' => fn() => array('status' => 'OK')
        )
    )
);
