<?php
namespace Scoop\View;

abstract class Heritage {
    private static $content = array();
    private static $footer = array();
    private static $data;

    /**
     * Inicia los atributos estaticos de la clase.
     * @param array<mixed> $data Los datos que seran renderizados por la plantilla.
     */
    public static function init($data)
    {
        self::$data = $data;
        array_unshift(self::$footer, '');
        if (ob_get_length()) {
            array_unshift(self::$content, trim(ob_get_contents()));
            ob_flush();
        }
        ob_start();
    }

    /**
     * Invoca una plantilla padre a la vista.
     * @param string $parent Ubicacion del template que sera aplicado a la vista.
     */
    public static function extend($parent)
    {
        extract(self::$data);
        require Template::parse($parent);
        $index = count(self::$footer) - 1;
        self::$footer[$index] = trim(ob_get_contents());
        ob_end_clean();
    }

    /**
     * Incluye un template dentro de otro.
     * @param string $path Ruta donde se ubica la vista a ser incluida.
     */
    public static function getCompilePath($path)
    {
        return Template::parse($path);
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
        $view = ob_get_contents().array_shift(self::$footer);
        while (ob_get_length() !== false) {
            ob_end_clean();
        }
        echo array_shift(self::$content);
        return $view;
    }
}
