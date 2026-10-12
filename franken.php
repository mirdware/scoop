<?php

require 'scoop/Bootstrap/Application.php';

$app = new \Scoop\Bootstrap\Application('app/context');
$maxRequests = max(0, (int) getenv('MAX_REQUESTS'));
$handler = static function () use (&$app) {
    try {
        $app->run();
    } catch (Exception $ex) {
        reportUncatchableException($ex);
    } catch (\Throwable $ex) {
        reportUncatchableException($ex);
    }
};

function reportUncatchableException($ex) {
    error_log($ex);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Internal Server Error';
    }
}

for ($requests = 0; !$maxRequests || $requests < $maxRequests; ++$requests) {
    $keepRunning = frankenphp_handle_request($handler);
    $app->terminate();
    gc_collect_cycles();
    if (!$keepRunning) {
        break;
    }
}
