<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PSR (http://www.php-fig.org/psr/).
 * StudlyCaps: Clases, Interfaces, Namespaces, Packages
 * camelCase: Métodos, Propiedades, Párametro, Variable
 * ALL_CAPS: Constantes
 *
 * @package Scoop
 * @license http://opensource.org/licenses/MIT MIT
 * @author  Marlon Ramírez <marlonramirez@outlook.com>
 * @link http://getscoop.org
 * @version 0.5
 */

require 'scoop/Context.php';
\Scoop\Context::load();
$environment = new \Scoop\Bootstrap\Environment('app/config');
$app = new \Scoop\Bootstrap\Application($environment);
try {
    echo $app->run();
} catch (\Scoop\Http\Exception $ex) {
    echo $app->showError($ex->handler());
}
