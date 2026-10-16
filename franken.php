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
    frankenphp_log((string) $ex, FRANKENPHP_LOG_LEVEL_ERROR);
    if (!headers_sent()) {
        http_response_code(500);
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
