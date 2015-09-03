<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PSR.
 * Clases/Interfaces: PascalCase <http://localhost/class-to-pascal-case/>
 * Métodos: camelCase <http://localhost/class-to-pascal-case/method-to-camel-case/>
 * Constantes: ALL_CAPS
 * Namespaces/Packages: PascalCase
 * Propiedades/Párametro/Variable: camelCase
 *
 * @package Scoop
 * @license http://opensource.org/licenses/MIT MIT
 * @author  Marlon Ramírez <marlonramirez@outlook.com>
 * @link http://getscoop.org
 * @version 0.2.2 new system of routing
 */

try {
    require 'scoop/Bootstrap/Loader.php';
    $environment = new \Environment\Production();
    $app = new \Scoop\Bootstrap\App($environment);
    $environment->configure();
    $app->run();
} catch (\Scoop\Http\Exception $ex) {
    $ex->handler();
}
