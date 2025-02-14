<?php

return array(
    'app' => 'json:package',
    'messages' => array(
        'es' => 'import:app/config/lang/es',
        'en' => 'import:app/config/lang/en'
    ),
    'db' => require 'config/db.php',
    'routes' => require 'config/routes.php',
    'providers' => require 'config/providers.php'
);
