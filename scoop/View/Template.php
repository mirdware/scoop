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
        $search = array_merge($search, array(': ?> <?php ', ' ?> <?php ', ': ?><?php ', ' ?><?php ', '{{=', '{{', '}}'));
        $matches = array_merge($matches, array(':', ';', ':', ';', '<?php echo(', '<?php echo #view->escape(',  ') ?>'));
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
        fwrite($view, self::convertViewServices($content));
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
            $head = preg_replace('/>\s+</', '><', $matches[2]);
            $html = str_replace($matches[0], "<html {$matches[1]}><head>$head</head><body>", $html);
        }
        $html = preg_replace(
            array('/\s+/', '/<!--.*?-->/s', '/<\/\s*(\w+)\s*>\s*<\/(\w+)>/', '/(;|=)\s*(\"|\')/', '/\s*(\/?)\s*>/', '/<\s/'),
            array(' ', '', '</${1}></${2}>', '${1}${2}', '${1}>', '<' ),
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
        return $content;
    }

    private static function replace($line)
    {
        if (self::replaceSingle($line)) {
            return $line;
        }
        $quotes = '\'[^\']*\'|"[^"]*"';
        $safeChars = '[\(\)\d\s\.\+\-\*\/%=]|true|false|null';
        $vars = '(\$|#)?[\w_]+(::[\w_]+|->[\w_]+|\[(' . $quotes . '|\d+|\$\w+)\])*';
        $conditional = $safeChars . '|' . $vars . '|[<>!]|and|or';
        $fn = '\((' . $quotes . '|' . $safeChars . '|' . $vars . '|,|\[.*\]|array\(.*\))*\)';
        $safeExp = $quotes . '|' . $conditional . '|' . $fn;
        $uri = '(\w+:)?[\$\w\/-]+';
        $line = preg_replace(array(
            "/@inject\s([\\\\\w]+)#(\w+)/",
            "/@extends\s('$uri')/",
            "/@import\s('$uri'|\"$uri\")/",
            "/@foreach\s(($vars)+\s+as\s+($vars)+(\s*=>\s*($vars)+)?)/"
        ), array(
            '<?php ' . self::SERVICE . '::inject(\'${2}\',\'${1}\') ?>',
            '<?php require #view->getCompilePath(${1});#view->setParent() ?>',
            '<?php require #view->getCompilePath(${1}) ?>',
            '<?php foreach(${1}): ?>'
        ), $line, 1, $count);
        if ($count !== 0) {
            return $line;
        }
        if (self::replaceRegex($line, "/@(if|elseif|while)\s(($safeExp)+)/")) {
            return $line;
        }
        self::replaceRegex($line, "/@(for)\s(($vars|$safeChars|$quotes|,|$fn)*;($conditional)+;($vars|$safeChars)*)/");
        return $line;
    }

    private static function replaceSingle(&$line)
    {
        $line = str_replace(
            array(':if', ':foreach', ':for', ':while', '@else'),
            array('<?php endif ?>', '<?php endforeach ?>', '<?php endfor ?>', '<?php endwhile ?>', '<?php else: ?>'),
            $line, $count
        );
        return $count !== 0;
    }

    private static function replaceRegex(&$line, $regex)
    {
        $line = preg_replace_callback($regex, function ($matches) {
            return '<?php ' . $matches[1] . '(' . trim($matches[2]) . '): ?>';
        }, $line, 1, $count);
        return $count !== 0;
    }

    private static function convertViewServices($content)
    {
        preg_match_all('/\<\?php.*?\?>/is', $content, $tagsFound);
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
            $contentValue = "<?php ob_start() ?>$contentValue<?php $variable=ob_get_clean();";
        }
        return "{$contentValue}echo #view->compose('$componentName', $propsPhpString, $variable); ?>";
    }
}
