<?php
namespace scoop\view;

abstract class Maker extends \scoop\View {
	private static $footer = '';

	public static function expand ( $parent, &$dataView ) {
		if ( self::$flagLayer ) {
			extract($dataView);
			require self::ROOT_VIEWS.$parent.self::EXT_VIEWS;
			self::$footer = ob_get_contents();
			ob_clean();
		}
	}

	public static function output () {
		ob_start( array('\scoop\view\Maker', 'publish') );
	}

	private static function publish ($buffer) {
		return $buffer.self::$footer;
	}
}