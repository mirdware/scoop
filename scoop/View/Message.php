<?php
namespace Scoop\View;

/**
 * Clase componente de los mensajes usados por el Bootstrap.
 */
class Message implements Component
{
    /**
     * Tipo de salida estandar de los mensajes.
     */
    const INFO = 'info';
     /**
     * Tipo de salida como exito
     */
    const SUCCESS = 'success';
    /**
     * Tipo de salida como error.
     */
    const ERROR = 'error';
    /**
     * Tipo de salida como advertencia.
     */
    const WARNING = 'warning';
    /**
     * @var array Propiedades del mensaje.
     */
    private static $props = array(
        'type' => 'not',
        'msg' => ''
    );

    /**
     * Crea el componente en la vista.
     * @return string Propiedad $template.
     */
    public function render()
    {
        extract(self::$props);
        return "<div id=\"msg\" class=\"$type\"><i class=\"close\"></i><span>$msg</span></div>";
    }

    /**
     * Valida y muestra el mensaje suministrado por el usuario.
     * @param string $msg  Mensaje a ser mostrado por la aplicación.
     * @param string $type Tipo de mensaje a mostrar.
     */
    public static function push($msg, $type = self::SUCCESS)
    {
        self::validate($type);
        $_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
    }

    /**
     * Muestra y elimina el mensaje suministrado por el usuario.
     */
    public static function pull()
    {
        if (isset($_SESSION['msg-scoop'])) {
            self::$props = $_SESSION['msg-scoop'];
            unset($_SESSION['msg-scoop']);
        }
    }

    /**
     * Valida y muestra el mensaje suministrado por el usuario.
     * @param string $msg  Mensaje a ser mostrado por la aplicación.
     * @param string $type Tipo de mensaje a mostrar.
     */
    public static function set($msg, $type = self::SUCCESS)
    {
        self::validate($type);
        self::$props = array('type'=>$type, 'msg'=>$msg);
    }

    /**
     * Verifica que el tipo de mensaje enviado sea valido.
     * @param string $type Tipo de mensaje.
     * @throws \UnexpectedValueException Si no es un tipo de mensaje valido.
     */
    private static function validate($type)
    {
        $class = new \ReflectionClass(get_class());
        if (!in_array($type, $class->getConstants())) {
            throw new \UnexpectedValueException('Error building the message [type rejected].');
        }
    }
}
