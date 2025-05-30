<?php

namespace Scoop\View;

abstract class Heritage
{
    private static $parent;
    private static $templates;

    /**
     * Inicia los atributos estaticos de la clase.
     * @param array<mixed> $data Los datos que seran renderizados por la plantilla.
     */
    public static function init()
    {
        $environment = \Scoop\Context::inject('\Scoop\Bootstrap\Environment');
        self::$templates = array_merge(
            array('sdt.php' => 'Scoop\View\Template'),
            $environment->getConfig('templates', array())
        );
        ob_start();
    }

    /**
     * Incluye un template dentro de otro.
     * @param string $path Ruta donde se ubica la vista a ser incluida.
     */
    public static function getCompilePath($path)
    {
        $infoPath = pathinfo($path);
        $ext = (
            isset($infoPath['extension']) &&
            isset(self::$templates[$infoPath['extension']])
        ) ? $infoPath['extension'] : 'sdt.php';
        $path = $infoPath['dirname'] . '/' . $infoPath['filename'];
        $template = \Scoop\Context::inject(self::$templates[$ext]);
        return $template->parse($path);
    }

    /**
     * Se aplica a un template en el lugar en donde la vista hija debe ser incluida.
     */
    public static function setParent()
    {
        $content = ob_get_clean();
        if (isset(self::$parent)) {
            self::$parent = self::parseBlocks($content, self::$parent);
        } else {
            self::$parent = $content;
        }
        ob_start();
    }

    public static function parseBlocks($content, $parent)
    {
        $res =preg_replace_callback('#@block\[(\w+)\]\s*(.*?):block#', function ($matches) use (&$parent) {
            $parent = str_replace("@slot[{$matches[1]}]", $matches[2], $parent, $count);
            return $count ? '' : $matches[0];
        }, $content);
        return preg_replace('#@slot([^\[])#', trim($res) . '$1', $parent);
    }

    /**
     * Obtiene el contenido de la vista y limpia el buffer.
     * @return string Pie de página del template.
     */
    public static function getContent()
    {
        if (isset(self::$parent)) {
            return self::parseBlocks(ob_get_clean(), self::$parent);
        }
        return ob_get_clean();
    }
}
