<?php
/**
 * Scoop (Simple components of Object-Oriented Programming) apoya el uso de convenciones PSR (http://www.php-fig.org/psr/).
 * StudlyCaps: Clases, Interfaces, Namespaces, Packages
 * camelCase: Métodos, Propiedades, Párametro, Variable
 * ALL_CAPS: Constantes
 *
 * @package Scoop
 * @license http://opensource.org/licenses/MIT MIT
 * @author  Marlon Ramírez <marlonramirez@outlook.com>
 * @link http://getscoop.org
 * @version 0.9
 */

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
