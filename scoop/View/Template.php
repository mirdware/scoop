<?php

namespace Scoop\View;

final class Template
{
    const SERVICE = '\Scoop\View\Service';
    private static $cachePath = 'app/storage/cache/views';
    private static $viewPath = 'app/views/';

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

    protected function create($viewName, $templateName)
    {
        $content = self::compile($templateName);
        preg_match_all('/<pre[^>]*>.*?<\/pre>/is', $content, $matches);
        $matches = $matches[0];
        $content = self::clearHTML($content);
        $content = preg_replace_callback(
            '~<sc-([\.a-zA-Z0-9_-]+)
            \s*((?:\s+[a-zA-Z0-9_-]+\s*=\s*(?:\{.+?\}|"[^"]*"|\'[^\']*\'))*)?
            \s*>(.*?)<\/sc-\1>~six',
            array('Scoop\View\Template', 'parseCustomTag'),
            $content
        );
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

    public static function clearHTML($html)
    {
        $blockElements = array(
            'div', 'main', 'address', 'article', 'aside', 'blockquote', 'canvas', 'dd', 'dl', 'dt', 'fieldset',
            'figcaption', 'figure', 'footer', 'form', 'h\d', 'header', 'hr', 'li', 'nav', 'noscript', 'ol', 'p',
            'pre', 'section', 'table', 'tfoot', 'tbody', 'ul', 'video', 'script', 'style', 'td', 'th', 'tr', 'option'
        );
        preg_match(
            '/\s*<\s*html\s*(.*?)\s*>\s*<\s*head\s*>\s*(.*?)\s*<\s*\/\s*head\s*>\s*<\s*body\s*>\s*/s',
            $html,
            $matches
        );
        if (isset($matches[0])) {
            $head = str_replace(array('[php', 'php]'), array('<?php', '?>'), $matches[2]);
            $head = preg_replace('/>\s+</', '><', $head);
            $html = str_replace($matches[0], '<html ' . $matches[1] . '><head>' . $head . '</head><body>', $html);
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
            '[php require ' . self::SERVICE . '::get(\'view\')->getCompilePath(${1});' . self::SERVICE . '::get(\'view\')->setParent() php]',
            '[php require ' . self::SERVICE . '::get(\'view\')->getCompilePath(${1}) php]',
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
                '@else'
            ),
            array(
                '[php endif php]',
                '[php endforeach php]',
                '[php endfor php]',
                '[php endwhile php]',
                '[php else: php]'
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

    private static function parseCustomTag($matches)
    {
        $componentName = $matches[1];
        $attributesString = $matches[2] ? $matches[2] : '';
        $contentValue = trim($matches[3]);
        $props = array();
        $attrPattern = '~([a-zA-Z0-9_-]+)\s*=\s*(?:\{(.+?)\}|"([^"]*)"|\'([^\']*)\')~ix';
        if (preg_match_all($attrPattern, $attributesString, $attr_matches, PREG_SET_ORDER)) {
            foreach ($attr_matches as $attr) {
                $propName = $attr[1];
                $propValuePhpCode = '';
                if (!empty($attr[2])) {
                    $propValuePhpCode = trim($attr[2]);
                } elseif (isset($attr[3])) {
                    $propValuePhpCode = "'" . addslashes($attr[3]) . "'";
                } elseif (isset($attr[4])) {
                    $propValuePhpCode = "'" . addslashes($attr[4]) . "'";
                }
                $props[$propName] = $propValuePhpCode;
            }
        }
        $propsPhpArray = array();
        foreach ($props as $key => $phpCodeForValue) {
            $propsPhpArray[] = "'" . addslashes($key) . "' => " . $phpCodeForValue;
        }
        $propsPhpString = 'array(' . implode(', ', $propsPhpArray) . ')';
        if (empty($contentValue)) {
            $variable = '\'\'';
            $contentValue = '<?php ';
        } else {
            $variable = uniqid('$t');
            $contentValue = "$contentValue<?php $variable = ob_get_clean();";
        }
        return $contentValue . 'echo ' . self::SERVICE . "::get('view')->compose('$componentName', $propsPhpString, $variable); ?>";
    }
}
