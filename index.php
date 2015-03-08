<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PSR.
 * Clases: PascalCase <http://localhost/class-to-pascal-case/>
 * Métodos: camelCase <http://localhost/class-to-pascal-case/method-to-camel-case/>
 * constantes: ALL_CAPS
 * Namespace / Package: PascalCase
 * Propiedades: camelCase
 * Párametro: camelCase
 * Variable: camelCase
 * Interface: PascalCase
 * Usa PHP como si se tratase de un lenguaje case sensitive.
 *
 * @package scoop
 * @author  Marlon Ramirez <marlonramirez@outlook.com>
 */

try {
    require 'scoop/bootstrap/Loader.php';
    Loader::get();

    \Scoop\View\Template::addClass('View', '\Scoop\View\Helper');
    \Scoop\Bootstrap\App::run();

} catch (\Scoop\Http\Exception $ex) {
    $ex->handler();
}
