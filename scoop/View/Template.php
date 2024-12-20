<?php

namespace Scoop\View;

final class Template
{
    const HERITAGE = '\Scoop\View\Heritage';
    const SERVICE = '\Scoop\View\Service';
    private static $cachePath = 'app/storage/cache/views';
    private static $viewPath = 'app/views/';

    /**
     * Convierte las platillas sdt a vistas php, en caso que la vista sea más
     * antiguas que el template.
     * @param string $templatePath Nombre de la plantilla en formato name.sdt.php.
     * @param array<mixed>  $viewData Datos que deben ser reemplazados dentro de la vista.
     * @throws \UnderflowException No se puede generar la vista, pues no existe template.
     */
    public function parse($templatePath)
    {
        $template = self::$viewPath . $templatePath . '.sdt.php';
        $view = self::$cachePath . $templatePath . '.php';
        if (!is_readable($template)) {
            throw new \UnderflowException('Unable to load view or template ' . $templatePath);
        }
        if (is_readable($view) && filemtime($view) > filemtime($template)) {
            return $view;
        }
        $this->create($view, $template);
        return $view;
    }

    public static function setPath($viewPath, $cachePath)
    {
        self::$viewPath = $viewPath;
        self::$cachePath = $cachePath;
    }

    /**
     * Almacena la vista PHP en el disco.
     * @param string $viewName Nombre de la vista a almacenar.
     * @param string $content Contenido de la plantilla aplicando los reemplazos.
     */
    protected function create($viewName, $templateName)
    {
        $content = self::compile($templateName);
        preg_match_all('/<pre[^>]*>.*?<\/pre>/is', $content, $matches);
        $matches = $matches[0];
        $content = self::clearHTML($content);
        $search = array_map(array('\scoop\view\Template', 'clearHTML'), $matches);
        $content = str_replace(array('[php', 'php]'), array('<?php', '?>'), $content);
        $search += array(': ?><?php ', ' ?><?php ');
        $matches += array(':', ';');
        $content = str_replace($search, $matches, $content);
        $path = explode('/', $viewName);
        $count = count($path) - 1;
        $dir = '';
        for ($i = 0; $i < $count; $i++) {
            $dir .= $path[$i] . '/';
            if (!file_exists($dir)) {
                mkdir($dir, 0700);
            }
        }
        $view = fopen($viewName, 'w');
        fwrite($view, $content);
        fclose($view);
    }

    /**
     * Reglas para limpiar y minificar la vista.
     * @param  string $html Contenido completo de la plantilla.
     * @return string Plantilla limpia y minificada.
     */
    public static function clearHTML($html)
    {
        $blockElements = array(
            'div', 'main', 'address', 'article', 'aside', 'blockquote', 'canvas', 'dd', 'dl', 'dt', 'fieldset',
            'figcaption', 'figure', 'footer', 'form', 'h\d', 'header', 'hr', 'li', 'nav', 'noscript', 'ol', 'p',
            'pre', 'section', 'table', 'tfoot', 'tbody', 'ul', 'video', 'script', 'style', 'td', 'th', 'tr', 'option'
        );
        preg_match(
            '/\s*<\s*html([^>]*)>\s*<\s*head\s*>\s*(.*?)\s*<\s*\/\s*head\s*>\s*<\s*body\s*>\s*/s',
            $html,
            $matches
        );
        if (isset($matches[0])) {
            $head = str_replace(array('[php', 'php]'), array('<?php', '?>'), $matches[2]);
            $head = preg_replace('/>\s+</', '><', $head);
            $html = str_replace($matches[0], '<html' . $matches[1] . '><head>' . $head . '</head><body>', $html);
        }
        $html = preg_replace(
            array(
                '/\s+/',
                '/<!--.*?-->/s',
                '/<\/\s*(\w+)\s*>\s*<\/(\w+)>/',
                '/(;|=)\s*(\"|\')/',
                '/\s*(\/?)\s*>/',
                '/<\s/'
            ),
            array(
                ' ',
                '',
                '</${1}></${2}>',
                '${1}${2}',
                '${1}>',
                '<'
            ),
            $html
        );
        foreach ($blockElements as $element) {
            $html = preg_replace('/[\s\r\n]*(<\/?' . $element . '[^>]*>)[\s\r\n]*/is', '${1}', $html);
        }
        return $html;
    }

    /**
     * Compila el template para convertir su sintaxis a PHP puro, generando de esta manera la vista.
     * @param string $template Nombre del template que se usara
     * @return string Contenido básico de la vista a ser mostrada
     */
    private static function compile($template)
    {
        $content = '';
        $file = fopen($template, 'r');
        while (!feof($file)) {
            $content .= self::replace(fgets($file));
        }
        fclose($file);
        return self::convertViewServices($content);
    }

    /**
     * Reglas de reemplazo para cada uno de los comandos de la plantilla.
     * EJ: @extends 'template' => \Scoop\View\Helper::extend('template').
     * @param string $line Linea que se encuentra analizando el parseador.
     * @return string Linea con los cambios realizados.
     */
    private static function replace($line)
    {
        $quotes = '\'[^\']*\'|"[^"]*"';
        $safeChars = '[\(\)\d\s\.\+\-\*\/%=]|true|false|null';
        $vars = '(\$|#)?[\w_]+(::[\w_]+|->[\w_]+|\[(' . $quotes . '|\d+|\$\w+)\])*';
        $conditional = $safeChars . '|' . $vars . '|[<>!]|and|or';
        $fn = '\((' . $quotes . '|' . $safeChars . '|' . $vars . '|,|\[.*\]|array\(.*\))*\)';
        $safeExp = $quotes . '|' . $conditional . '|' . $fn;
        $uri = '(\w+:)?[\$\w\/-]+';
        $line = preg_replace(array(
            "/@inject ([\\\\\w]+)#(\w+)/",
            "/@extends ('$uri')/",
            "/@import ('$uri'|\"$uri\")/",
            "/@if (($safeExp)+)/",
            "/@elseif (($safeExp)+)/",
            "/@while (($conditional|$fn)+)/",
            "/@foreach (($vars)+\s+as\s+($vars)+(\s*=>\s*($vars)+)?)/",
            "/@for (($vars|$safeChars|$quotes|,|$fn)*;($conditional)+;($vars|$safeChars)*)/"
        ), array(
            '[php ' . self::SERVICE . '::inject(\'${2}\',\'${1}\') php]',
            '[php ' . self::HERITAGE . '::extend(${1}) php]',
            '[php require ' . self::HERITAGE . '::getCompilePath(${1}) php]',
            '[php if(${1}): php]',
            '[php elseif(${1}): php]',
            '[php while(${1}): php]',
            '[php foreach(${1}): php]',
            '[php for(${1}): php]'
        ), $line, 1, $count);
        if ($count !== 0) {
            return $line;
        }
        $line = str_replace(
            array(
                ':if',
                ':foreach',
                ':for',
                ':while',
                '@else',
                '@sprout'
            ),
            array(
                '[php endif php]',
                '[php endforeach php]',
                '[php endfor php]',
                '[php endwhile php]',
                '[php else: php]',
                '[php ' . self::HERITAGE . '::sprout() php]'
            ),
            $line,
            $count
        );
        if ($count !== 0) {
            return $line;
        }
        return str_replace(array('{{', '}}'), array('[php echo ', ' php]'), $line);
    }

    private static function convertViewServices($content)
    {
        preg_match_all('/\[php.*?php\]/is', $content, $tagsFound);
        foreach ($tagsFound[0] as $search) {
            $replace = preg_replace('/#(\w*)->/is', self::SERVICE . '::get(\'${1}\')->', $search);
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
}
