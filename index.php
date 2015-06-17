<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PSR.
 * Clases/Interfaces: PascalCase <http://localhost/class-to-pascal-case/>
 * Métodos: camelCase <http://localhost/class-to-pascal-case/method-to-camel-case/>
 * Constantes: ALL_CAPS
 * Namespaces/Packages: PascalCase
 * Propiedades/Párametro/Variable: camelCase
 * Usa PHP como si se tratase de un lenguaje case sensitive.
 *
 * @package scoop
 * @license http://opensource.org/licenses/MIT MIT
 * @author  Marlon Ramirez <marlonramirez@outlook.com>
 * @link http://getscoop.org
 * @version 0.1.2 Modificaciones en el ruteo y configuración del bootstrap
 */

try {
    require 'scoop/Bootstrap/Loader.php';
    Loader::get();
    \Scoop\Bootstrap\Config::add('app/config');
    \Scoop\Bootstrap\App::run();
} catch (\Scoop\Http\Exception $ex) {
    $ex->handler();
}
