<?php
namespace Scoop\View;

/**
 * Se encarga de convertir las plantillas dadas en formato nombre.sdt.php a
 * vistas PHP
 */
final class Template
{
    /**
     * Ruta donde se encuentran las platillas.
     */
    const ROOT = 'app/views/';
    /**
     * Extensión de los archivos que funcionan como plantillas.
     */
    const EXT = '.sdt.php';
    /**
     * Nombre de la clase que se encarga del manejo de la herencia.
     */
    const HERITAGE = '\Scoop\View\Heritage';

    /**
     * Convierte las platillas sdt a vistas php, en caso que la vista sea más
     * antiguas que el template.
     * @param string $templatePath Nombre de la plantilla en formato name.sdt.php.
     */
    public static function parse($templatePath)
    {
        $template = self::ROOT.$templatePath.self::EXT;
        $view = \Scoop\View::ROOT . $templatePath . \Scoop\View::EXT;
        $existView = is_readable($view);
        $existTemplate = is_readable($template);
        if ($existView) {
            if (!$existTemplate || filemtime($template) < filemtime($view)) {
                return;
            }
        } elseif (!$existTemplate) {
            throw new \UnderflowException('Unable to load view or template');
        }
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
            $lastLine = $line;
        }
        fclose($file);
        $content .= $lastLine;
        if ($flagPHP) {
            $content .= ' ?>';
        }
        self::create($view, $content);
    }

    /**
     * Reglas de reemplazo para cada uno de los comandos de la plantilla.
     * EJ: @extends 'template' => \Scoop\View\Helper::extend('template').
     * @param string $line Linea que se encuentra analizando el parseador.
     * @return boolean Existio o no reemplazo dentro de la linea.
     */
    private static function replace(&$line)
    {
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
        if ($count !== 0) {
            return true;
        }
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
        if ($count !== 0) {
            \Scoop\IoC\Service::compileView($line);
            return true;
        }
        $line = preg_replace('/\{([\w\s\.\$\[\]\(\)\'\"\/\+\*\-\?:=!<>,#]+)\}/',
        '<?php echo ${1} ?>', $line, -1, $count);
        if ($count !== 0) {
            \Scoop\IoC\Service::compileView($line);
        }
        return false;
    }

    /**
     * Reglas para limpiar y minificar la vista.
     * @param string $html Contenido completo de la plantilla.
     * @return string Plantilla limpia y minificada.
     */
    private static function clearHTML($html)
    {
        return preg_replace(array(
            '/<!--.*?-->/s',
            '/>\s*\n\s*</',
            '/;\s*(\"|\')/',
            '/<\?php(.*)\s*:\s*(.*)\?>/',
            '/<\?php(.*)\s*;\s*\?>/',
            '/\s+/'
        ), array(
            '',
            '><',
            '${1}',
            '<?php${1}:${2}?>',
            '<?php${1};${2}?>',
            ' '
        ), $html);
    }

    /**
     * Almacena la vista PHP en el disco.
     * @param string $viewName Nombre de la vista a almacenar.
     * @param string $content Contenido de la plantilla aplicando los reemplazos.
     */
    private static function create($viewName, &$content)
    {
        $content = preg_replace(
            array('/<\/\s+/', '/\s+\/>/', '/<\s+/', '/\s+>/'),
            array('</', '/>', '<', '>'),
            $content);
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
        fwrite($view, trim($content));
        fclose($view);
    }
}
