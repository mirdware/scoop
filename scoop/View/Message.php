<?php
namespace Scoop\View;

/**
 * Contenedor de los mensajes usados por el Bootstrap.
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
     * @var string Contenido del mensaje.
     */
    private static $template = '<div id="msg" class="not"><i class="close"></i><span></span></div>';

    /**
     * Crea el componente en la vista.
     * @return string Propiedad $template.
     */
    public function render()
    {
        return self::$template;
    }

    /**
     * Valida y muestra el mensaje suministrado por el usuario.
     * @param string $msg  Mensaje a ser mostrado por la aplicación.
     * @param string $type Tipo de mensaje a mostrar.
     */
    public static function push($msg, $type)
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
            self::setMsg($_SESSION['msg-scoop']['type'], $_SESSION['msg-scoop']['msg']);
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
        self::setMsg($type, $msg);
    }

    /**
     * Establece la plantilla para el tipo enviado.
     * @param string $type Tipo de mensaje: out, warning, error.
     * @param string $msg Descripción del mensaje enviado por el usuario.
     */
    private static function setMsg($type, $msg = self::SUCCESS)
    {
        self::$template = '<div id="msg" class="'.$type.'"><i class="close"></i><span>'.$msg.'</span></div>';
    }

    /**
     * Verifica que el tipo de mensaje enviado sea valido.
     * @param string $type Tipo de mensaje.
     * @throws \Exception Si no es un tipo de mensaje valido.
     */
    private static function validate($type)
    {
        if ($type !== self::SUCCESS &&
            $type !== self::ERROR &&
            $type !== self::WARNING &&
            $type !== self::INFO) {
            throw new \UnexpectedValueException('Error building the message [type rejected].');
        }
    }
}
