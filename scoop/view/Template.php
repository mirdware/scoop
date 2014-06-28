<?php
namespace scoop\view;

use scoop\View as View;

final class Template {
	//ruta donde se encuentran las platillas
	const ROOT = 'app/templates/';
	//extención de los archivos que funcionan como plantillas
	const EXT = '.sdt.php';
	private static $content;
	private static $view;

	public static function parse ($name) {
		$template = self::ROOT.$name.self::EXT;
		$view = View::ROOT.$name.View::EXT;
		if ( filemtime($template) > filemtime($view) ) {
			self::$content = file_get_contents($template);
			self::$view =& $view;
			self::replace();
			self::create();
		}
	}

	private static function replace () {
		self::$content = preg_replace( '/\{([\w\s\.]+)\}/',
			'<?php echo ${1} ?>',
			self::$content );

		self::$content = preg_replace( '/@expand\(\'([\w\/]+)\'\)/', 
			'<?php \scoop\view\Maker::expand(\'${1}\', $view) ?>', 
			self::$content );
	}

	private static function create () {
	}

}