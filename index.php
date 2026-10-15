<?php

require 'scoop/Bootstrap/Application.php';

$app = new \Scoop\Bootstrap\Application('app/context');
try {
    $app->run();
} catch (Exception $ex) {
    $app->terminate();
    throw $ex;
} catch (Throwable $ex) {
    $app->terminate();
    throw $ex;
}
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
$app->terminate();
