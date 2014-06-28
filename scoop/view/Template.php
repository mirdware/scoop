<?php
namespace scoop\view;

use scoop\View as View;

final class Template {
	//ruta donde se encuentran las platillas
	const ROOT = 'app/templates/';
	//extención de los archivos que funcionan como plantillas
	const EXT = '.sdt.php';

	public static function parse ($name) {
		$template = self::ROOT.$name.self::EXT;
		$view = View::ROOT.$name.View::EXT;

		if ( is_readable($template) && 
			( !is_readable($view) || filemtime($template) > filemtime($view) ) ) {
			$content = '';
			$flagPHP = FALSE;
			$lastLine = '';
			$file = fopen($template, 'r');
			while ( !feof($file) ) {
				$line = trim(fgets($file));
				$flag = self::replace($line);
				
				if ($flagPHP) {
					$lastChar = ';';
					if (!$flag) {
						$lastChar = ' ?>';
						$flagPHP = FALSE;
					}
					$lastLine .= $lastChar;
					self::formatObj($line);
				} elseif ($flag) {
					$line = '<?php '.$line;
					$flagPHP = TRUE;
				}

				$content .= $lastLine;
				$lastLine = $line;
			}

			fclose($file);
			$content .= $lastLine;
			$content = preg_replace('/<!--(.|\n)*?-->/', '', $content);
			self::create( $view, $content );
		}
	}

	private static function formatObj (&$line) {
		$objs = explode('->', $line);
		$objs = array_map('ucfirst', $objs);
		$line = implode('->get', $objs);
		$line = preg_replace('/->get(\w+)(->|\s)/', '->get${1}()${2}', $line);
	}

	private static function replace (&$line) {
		$line = preg_replace('/@expand\s\'([\w\/]+)\'/', 
			'\scoop\view\Maker::expand(\'${1}\', $view)', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = preg_replace('/@if\s([\w\s\.\&\|\$!=<>\-]+)/',
			'if(${1}):', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = preg_replace('/@elseif\s([\w\s\.\&\|\$!=<>\-]+)/',
			'elseif(${1}):', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = preg_replace('/@foreach\s([\w\s\.\&\|\$\->]+)/',
			'foreach(${1}):', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = preg_replace('/@for\s([\w\s\.\&\|\$;\(\)!=<>\+\-]+)/',
			'for(${1}):', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = preg_replace('/@while\s([\w\s\.\&\|\$\(\)!=<>\+\-]+)/',
			'while(${1}):', 
			$line, 1, $count);
		if ($count != 0) return TRUE;

		$line = str_replace( array(
				':if', 
				':foreach',
				':for',
				':while',
				'@else',
				'@output'
			), array(
				'endif', 
				'endforeach',
				'endfor',
				'endwhile',
				'else:',
				'\scoop\view\Maker::output()'
			), $line, $count);
		if ($count != 0) return TRUE;
		
		$line = preg_replace( '/\{([\w\s\.\$\[\]\'\'\"\"\->]+)\}/',
			'<?php echo ${1} ?>',
			$line, -1, $count);
		if ($count != 0) self::formatObj($line);
		return FALSE;
	}

	private static function create ( $viewName, &$content ) {
		$view = fopen($viewName, 'w');
		fwrite($view, $content);
		fclose($view);
	}

}