<?php
namespace Scoop\View;

final class Template
{
    //ruta donde se encuentran las platillas
    const ROOT = 'app/views/templates/';
    //extensión de los archivos que funcionan como plantillas
    const EXT = '.sdt.php';
    //clase para el manejo de herencia
    const HERITAGE = '\Scoop\View\Heritage';
    //clases añadidas al template
    private static $classes = array();

    public static function parse($name)
    {
        $template = self::ROOT.$name.self::EXT;
        $view = \Scoop\View::ROOT . $name . \Scoop\View::EXT;

        if (is_readable($template) &&
            (!is_readable($view) || filemtime($template) > filemtime($view))) {
            $content = '';
            $flagPHP = false;
            $lastLine = '';
            $file = fopen($template, 'r');
            while (!feof($file)) {
                $line = fgets($file);
                $flag = self::replace($line);

                if ($flagPHP) {
                    $lastChar = ';';
                    if (!$flag) {
                        $lastChar = ' ?>';
                        $flagPHP = false;
                    }
                    $lastLine .= $lastChar;
                } elseif ($flag) {
                    $line = '<?php '.$line;
                    $flagPHP = true;
                }

                $content .= $lastLine;
                $lastLine = $line;
            }

            fclose($file);
            $content .= $lastLine;
            if ($flagPHP) {
                $content .= ' ?>';
            }
            self::create($view, $content);
        }
    }

    public static function addClass($key, $class)
    {
        self::$classes[$key] = $class;
    }

    private static function formatObj(&$line)
    {
        if ( strpos($line, '->') !== -1 ) {
            $objs = explode('->', $line);
            $init = array_shift($objs);
            $objs = array_map('ucfirst', $objs);
            $line = $init.implode('->get', $objs);
            $line = preg_replace('/->get(\w+)(->|\s)/', '->get${1}()${2}', $line);
            $line = str_replace(array_keys(self::$classes), self::$classes, $line);
        }
    }

    private static function replace(&$line)
    {
        $line = preg_replace(array(
                '/@expand\s\'([\w\/-]+)\'/',
                '/@include\s\'([\w\/-]+)\'/',
                '/@if\s([\w\s\.\&\|\$!=<>\/\+\*\\-]+)/',
                '/@elseif\s([\w\s\.\&\|\$!=<>\/\+\*\\-]+)/',
                '/@foreach\s([\w\s\.\&\|\$\->:]+)/',
                '/@for\s([\w\s\.\&\|\$;\(\)!=<>\+\-]+)/',
                '/@while\s([\w\s\.\&\|\$\(\)!=<>\+\-]+)/'
            ), array(
                self::HERITAGE.'::expand(\'${1}\')',
                'include \Scoop\View::ROOT.\'${1}\'.\Scoop\View::EXT',
                'if(${1}):',
                'elseif(${1}):',
                'foreach(${1}):',
                'for(${1}):',
                'while(${1}):'
            ), $line, 1, $count);
        if ($count !== 0) return true;

        $line = str_replace(array(
                ':if',
                ':foreach',
                ':for',
                ':while',
                '@else',
                '@beam'
            ), array(
                'endif',
                'endforeach',
                'endfor',
                'endwhile',
                'else:',
                self::HERITAGE.'::beam()'
            ), $line, $count);
        if ($count !== 0) return true;

        $line = preg_replace('/\{([\w\s\.\$\[\]\(\)\'\"\/\+\*\-\?:=!<>]+)\}/',
            '<?php echo ${1} ?>',
            $line, -1, $count);
        if ($count !== 0) self::formatObj($line);
        return false;
    }

    private static function clearHTML($html)
    {
        return preg_replace(array(
            '/<!--.*?-->/s',
            '/>\s*\n\s*</',
            '/;\s*(\"|\')/',
            '/\s*:\s*/',
            '/s*;\s*/',
            '/\s+/'
        ), array(
            '',
            '><',
            '${1}',
            ':',
            ';',
            ' '
        ), $html);
    }

    private static function create($viewName, &$content)
    {
        //normalizar las etiquetas
        $content = preg_replace(array(
            '/<\/\s+/',
            '/\s+\/>/',
            '/<\s+/',
            '/\s+>/'
        ), array(
            '</',
            '/>',
            '<',
            '>'
        ), $content);

        preg_match_all('/<pre[^>]*>.*?<\/pre>/is', $content, $match);
        $match = $match[0];
        $content = self::clearHTML($content);
        $search = array_map(array('\scoop\view\Template', 'clearHTML'), $match);
        $search += array(' ?><?php ');
        $match += array(';');
        $content = str_replace($search, $match, $content);

        $path = explode('/', $viewName);
        $count = count($path)-1;
        $dir = '';
        for ($i=0; $i<$count; $i++) {
            $dir .= $path[$i].'/';
            if (!file_exists($dir)) {
                mkdir($dir, 0700);
            }
        }
        $view = fopen($viewName, 'w');
        fwrite($view, $content);
        fclose($view);
    }

}
