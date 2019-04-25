<?php
namespace Scoop\View;

/**
 * Clase encargada de convertir las plantillas en formato nombre.sdt.php a
 * vistas PHP
 */
final class Template
{
    /**
     * Nombre de la clase que se encarga del manejo de la herencia.
     */
    const HERITAGE = '\Scoop\View\Heritage';

    /**
     * Convierte las platillas sdt a vistas php, en caso que la vista sea más
     * antiguas que el template.
     * @param string $templatePath Nombre de la plantilla en formato name.sdt.php.
     * @param array  $viewData Datos que deben ser reemplazados dentro de la vista.
     * @throws \UnderflowException No se puede generar la vista, pues no existe template.
     */
    public static function parse($templatePath, $viewData)
    {
        $template = 'app/views/'.$templatePath.'.sdt.php';
        $view = 'app/cache/views/'.$templatePath.'.php';
        if (is_readable($view)) {
            if (is_readable($template) && filemtime($template) > filemtime($view)) {
                self::create($view, self::compile($template));
            }
        } elseif (is_readable($template)) {
            self::create($view, self::compile($template));
        } else {
            throw new \UnderflowException('Unable to load view or template');
        }
        extract($viewData);
        require $view;
    }

    /**
     * Compila el template para convertir su sintaxis a PHP puro, generando de esta manera
     * la vista.
     * @param  string $template Nombre del template que se usara
     * @return string Contenido básico de la vista a ser mostrada
     */
    private static function compile($template)
    {
        $content = '';
        $flagPHP = false;
        $lastLine = '';
        $file = fopen($template, 'r');
        while (!feof($file)) {
            $line = fgets($file);
            $flag = self::replace($line);
            if ($flagPHP) {
                $lastChar = strpos($lastLine, ':') === strlen($lastLine)-1 ? '' : ';';
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
        return $content;
    }

    /**
     * Reglas de reemplazo para cada uno de los comandos de la plantilla.
     * EJ: @extends 'template' => \Scoop\View\Helper::extend('template').
     * @param  string  $line Linea que se encuentra analizando el parseador.
     * @return boolean Existio o no reemplazo dentro de la linea.
     */
    private static function replace(&$line)
    {
        $quotes = '\'[^\']*\'|"[^"]*"';
        $safeChars = '[\(\)\d\s\.\+\-\*\/%=]|true|false|null';
        $vars = '(\$|#)?[\w_]+(::[\w_]+|->[\w_]+|\[('.$quotes.'|\d+)\])*';
        $conditional = $safeChars.'|'.$vars.'|[<>!]|and|or';
        $fn = '\(('.$quotes.'|'.$safeChars.'|'.$vars.'|,|\[.*\]|array\(.*\))*\)';
        $safeExp = $quotes.'|'.$conditional.'|'.$fn;
        $simpleString = '\'([\w\/-]+)\'';
        $line = preg_replace(array(
            '/@extends '.$simpleString.'/',
            '/@import '.$simpleString.'/',
            '/@if (('.$safeExp.')+)/',
            '/@elseif (('.$safeExp.')+)/',
            '/@while (('.$conditional.'|'.$fn.')+)/',
            '/@foreach (('.$vars.')+\s+as\s+('.$vars.')+)/',
            '/@for (('.$vars.'|'.$safeChars.'|'.$quotes.'|,|'.$fn.')*;('.$conditional.')+;('.$vars.'|'.$safeChars.')*)/'
        ), array(
            self::HERITAGE.'::extend(\'${1}\')',
            self::HERITAGE.'::import(\'${1}\')',
            'if(${1}):',
            'elseif(${1}):',
            'while(${1}):',
            'foreach(${1}):',
            'for(${1}):'
        ), $line, 1, $count);
        if ($count !== 0) {
            $line = self::convertViewServices($line);
            return true;
        }
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
        $line = preg_replace('/\{(('.$safeExp.'|:|\?)+)\}/',
        '<?php echo ${1} ?>', $line, -1, $count);
        if ($count !== 0) {
            $line = self::convertViewServices($line);
        }
        return false;
    }

    private static function convertViewServices($line)
    {
        preg_match_all('/#(\w*)->/is', $line, $servicesFound);
        for ($i = 0; isset($servicesFound[0][$i]); $i++) {
            $line = str_replace($servicesFound[0][$i], '\Scoop\Context::getService(\''.$servicesFound[1][$i].'\')->', $line);
        }
        return $line;
    }

    /**
     * Reglas para limpiar y minificar la vista.
     * @param  string $html Contenido completo de la plantilla.
     * @return string Plantilla limpia y minificada.
     */
    private static function clearHTML($html)
    {
        return preg_replace(array(
            '/\s+/',
            '/[\t\n\r]+/',
            '/<!--.*?-->/s',
            '/>\s*</',
            '/;\s*(\"|\')/',
            '/<\/\s+/',
            '/\s+\/>/',
            '/<\s+/',
            '/\s+>/'
        ), array(
            ' ',
            ' ',
            '',
            '><',
            '${1}',
            '</',
            '/>',
            '<',
            '>'
        ), $html);
    }

    /**
     * Almacena la vista PHP en el disco.
     * @param string $viewName Nombre de la vista a almacenar.
     * @param string $content Contenido de la plantilla aplicando los reemplazos.
     */
    private static function create($viewName, $content)
    {
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
