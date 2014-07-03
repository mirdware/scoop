<?php
namespace scoop\view;

use scoop\View as View;

final class Template {
	//ruta donde se encuentran las platillas
	const ROOT = 'app/views/templates/';
	//extención de los archivos que funcionan como plantillas
	const EXT = '.sdt.php';
	private static $classes = array();

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
				$line = fgets($file);
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
			self::create( $view, $content );
		}
	}

	public static function addClass ($key, $class) {
		self::$classes[$key] = $class;
	}

	private static function formatObj (&$line) {
		if ( strpos($line, '->') != -1 ) {
			$objs = explode('->', $line);
			$init = array_shift($objs);
			$objs = array_map('ucfirst', $objs);
			$line = $init.implode('->get', $objs);
			$line = preg_replace('/->get(\w+)(->|\s)/', '->get${1}()${2}', $line);
			$line = str_replace(array_keys(self::$classes), self::$classes, $line);
		}
	}

	private static function replace (&$line) {
		$line = preg_replace( array(
				'/@expand\s\'([\w\/]+)\'/',
				'/@if\s([\w\s\.\&\|\$!=<>\-]+)/',
				'/@elseif\s([\w\s\.\&\|\$!=<>\-]+)/',
				'/@foreach\s([\w\s\.\&\|\$\->:]+)/',
				'/@for\s([\w\s\.\&\|\$;\(\)!=<>\+\-]+)/',
				'/@while\s([\w\s\.\&\|\$\(\)!=<>\+\-]+)/'
			), array( 
				'\scoop\view\Maker::expand(\'${1}\')',
				'if(${1}):',
				'elseif(${1}):',
				'foreach(${1}):',
				'for(${1}):',
				'while(${1}):'
			), $line, 1, $count);
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
		
		$line = preg_replace( '/\{([\w\s\.\$\[\]\(\)\'\'\"\"\->:]+)\}/',
			'<?php echo ${1} ?>',
			$line, -1, $count);
		if ($count != 0) self::formatObj($line);
		return FALSE;
	}

	private static function clearHTML ($html) {
		return preg_replace( array(
			'/>\s*\n\s*</',
			'/\s+/'
		), array(
			'><',
			' '
		), $html);
	}

	private static function create ( $viewName, &$content ) {
		$content = preg_replace( array(
			'/<!--.*?-->/s',
			'/<\/\s+/',
			'/\s+\/>/',
			'/<\s+/',
			'/\s+>/'
		), array(
			'',
			'</',
			'/>',
			'<',
			'>'
		), $content);

		preg_match_all('/<pre[^>]*>.*?<\/pre>/is', $content, $match);
		$match = $match[0];
		$content = self::clearHTML($content);
		$search = array_map( array('\scoop\view\Template', 'clearHTML'), $match);
		$content = str_replace($search, $match, $content);

		$dir = substr( $viewName, 0, strrpos($viewName, '/') ).'/';
		if ( !file_exists($dir) ) {
			mkdir($dir, 0700);
		}
		$view = fopen($viewName, 'w');
		fwrite($view, $content);
		fclose($view);
	}

}