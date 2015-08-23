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
                    $lastChar = strpos($lastLine, ':') === strlen($lastLine)-1? '': ';';
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
                $lastLine = trim($line);
            }

            fclose($file);
            $content .= $lastLine;
            if ($flagPHP) {
                $content .= ' ?>';
            }
            self::create($view, $content);
        }
    }

    private static function replace(&$line)
    {
        $line = preg_replace(array(
                '/@extends \'([\w\/-]+)\'/',
                '/@import \'([\w\/-]+)\'/',
                '/@if ([ \w\.\&\|\$!=<>\/\+\*\\-]+)/',
                '/@elseif ([ \w\.\&\|\$!=<>\/\+\*\\-]+)/',
                '/@foreach ([ \w\.\&\|\$\->:]+)/',
                '/@for ([ \w\.\&\|\$;,\(\)!=<>\+\-]+)/',
                '/@while ([ \w\.\&\|\$\(\)!=<>\+\-]+)/'
            ), array(
                self::HERITAGE.'::extend(\'${1}\')',
                self::HERITAGE.'::import(\'${1}\')',
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
                '@sprout'
            ), array(
                'endif',
                'endforeach',
                'endfor',
                'endwhile',
                'else:',
                self::HERITAGE.'::sprout()'
            ), $line, $count);
        if ($count !== 0) return true;

        $line = preg_replace('/\{([ \w\.\$\[\]\(\)\'\"\/\+\*\-\?:=!<>]+)\}/',
            '<?php echo ${1} ?>',
            $line, -1, $count);
        if ($count !== 0) \Scoop\IoC\Service::compileView($line);
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
