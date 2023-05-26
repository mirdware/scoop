<?php

namespace Scoop\View;

abstract class Heritage
{
    private static $stack = array();
    private static $data;
    private static $templates;

    /**
     * Inicia los atributos estaticos de la clase.
     * @param array<mixed> $data Los datos que seran renderizados por la plantilla.
     */
    public static function init($data)
    {
        $content = ob_get_contents();
        self::$data = $data;
        self::$templates = array_merge(
            array('sdt' => 'Scoop\View\Template'),
            \Scoop\Context::getEnvironment()->getConfig('templates', array())
        );
        array_push(self::$stack, array(
            'footer' => '',
            'content' => trim($content)
        ));
        ob_start();
    }

    /**
     * Invoca una plantilla padre a la vista.
     * @param string $parent Ubicacion del template que sera aplicado a la vista.
     */
    public static function extend($parent)
    {
        $template = \Scoop\Context::inject(self::$templates['sdt']);
        extract(self::$data);
        require $template->parse($parent);
        $index = count(self::$stack) - 1;
        $footer = &self::$stack[$index]['footer'];
        $footer = trim(ob_get_contents()) . $footer;
        ob_end_clean();
    }

    /**
     * Incluye un template dentro de otro.
     * @param string $path Ruta donde se ubica la vista a ser incluida.
     */
    public static function getCompilePath($path)
    {
        $key = 'sdt';
        $index = strpos($path, ':');
        if ($index) {
            $data = explode(':', $path);
            if (isset(self::$templates[$data[0]])) {
                $key = $data[0];
                $path = $data[1];
            }
        }
        $template = \Scoop\Context::inject(self::$templates[$key]);
        return $template->parse($path, self::$data);
    }

    /**
     * Se aplica a un template en el lugar en donde la vista hija debe ser incluida.
     */
    public static function sprout()
    {
        ob_start();
    }

    /**
     * Obtiene el contenido de la vista y limpia el buffer.
     * @return string Pie de página del template.
     */
    public static function getContent()
    {
        $item = array_pop(self::$stack);
        $view = ob_get_contents() . $item['footer'];
        ob_end_clean();
        return $view;
    }
}
