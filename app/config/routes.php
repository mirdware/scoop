<?php

return array(
    'home' => array(
        'url' => '/',
        'controller' => 'App\Controller\Home'
    ),
    'health-check' => array(
        'url' => '/health',
        'controller' => array(
            'get' => function() {
                return array('status' => 'OK');
            }
        )
    )
);
