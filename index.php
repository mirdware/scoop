<?php
/**
 * Scoop (Simple Characteristics of Object Oriented PHP) apoya el uso de convenciones PHP.
 * Clases: PascalCase <http://localhost/class-to-pascal-case/>
 * Métodos: camelCase <http://localhost/class-to-pascal-case/method-to-camel-case/>
 * constantes: ALL_CAPS
 * Namespace / Package: small_caps
 * Propiedades: camelCase
 * Párametro: camelCase
 * Variable: camelCase
 * Interface: PascalCase
 * Usa PHP como si se tratase de un lenguaje case sensitive.
 *
 * @package Scoop
 * @author  Marlon Ramirez <marlonramirez@outlook.com>
 */

try {
	require 'scoop/bootstrap/UniversalClassLoader.php';
	$loader = new UniversalClassLoader();
	$loader->useIncludePath( TRUE );
	$loader->register();

	\scoop\view\Template::addClass('View', '\scoop\view\Wrapper');
	\scoop\view\Template::addClass('Config', '\scoop\bootstrap\Config');
	\scoop\bootstrap\App::run();

} catch (\scoop\http\Exception $ex) {
	$ex->handler();
}