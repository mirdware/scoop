<?php
namespace Scoop\View;

/**
 * Clase que se encarga del manejo de herencia y relaciones dentro de las vistas.
 */
abstract class Heritage {
    /**
     * Almacena el pie de la página que actua como padre
     * @var string
     */
    private static $footer;
    /**
     * Almacena los datos que seran pasados a plantillas relacionadas.
     * @var array
     */
    private static $data;

    /**
     * Inicia los atributos estaticos de la clase.
     * @param array $data Los datos que seran renderizados por la plantilla.
     */
    public static function init($data)
    {
        self::$footer = '';
        self::$data = $data;
    }

    /**
     * Invoca una plantilla padre a la vista.
     * @param string $parent Ubicacion del template que sera aplicado a la vista.
     */
    public static function extend($parent)
    {
        Template::parse($parent, self::$data);
        self::$footer = trim(ob_get_contents()).self::$footer;
        ob_end_clean();
    }

    /**
     * Incluye un template dentro de otro.
     * @param string $path Ruta donde se ubica la vista a ser incluida.
     */
    public static function import($path)
    {
        Template::parse($path, self::$data);
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
        ob_end_clean();
        return $view;
    }
}
