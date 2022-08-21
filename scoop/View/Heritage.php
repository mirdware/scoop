<?php
namespace Scoop\View;

abstract class Heritage {
    private static $footer;
    private static $data;

    /**
     * Inicia los atributos estaticos de la clase.
     * @param array<mixed> $data Los datos que seran renderizados por la plantilla.
     */
    public static function init($data)
    {
        self::$footer = '';
        self::$data = $data;
        if (ob_get_length()) {
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
        self::$footer = trim(ob_get_contents()).self::$footer;
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
        $view = ob_get_contents().self::$footer;
        while (ob_get_length() !== false) {
            ob_end_clean();
        }
        return $view;
    }
}
