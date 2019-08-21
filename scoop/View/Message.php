<?php
namespace Scoop\View;

/**
 * Clase componente de los mensajes usados por el Bootstrap.
 */
class Message implements Component
{
    const INFO = 'info';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    private static $props;

    public function __construct()
    {
        self::$props = isset($_SESSION['msg-scoop']) ?
            $_SESSION['msg-scoop'] :
            array('type' => 'not', 'msg' => '');
    }

    /**
     * Crea el componente en la vista.
     * @return string Renderiza el mensaje.
     */
    public function render()
    {
        $type = self::$props['type'];
        $msg = self::$props['msg'];
        unset($_SESSION['msg-scoop']);
        return '<div id="msg" data-attr="className:type" class="'.$type.'"><i class="close"></i><span data-bind="msg">'.$msg.'</span></div>';
    }

    /**
     * Valida y muestra el mensaje suministrado por el usuario.
     * @param string $msg  Mensaje a ser mostrado por la aplicación.
     * @param string $type Tipo de mensaje a mostrar
     *  SUCCESS(por defecto): Se ejecuto correctamente la acción
     *  INFO: El mensaje es puramente informativo
     *  WARNING: Se debe prestar atención a algo que no detuvo el proceso
     *  ERROR: Se debe prestar atención a algo que detuvo el proceso.
     */
    public static function set($msg, $type = self::SUCCESS)
    {
        $class = new \ReflectionClass(get_class());
        if (!in_array($type, $class->getConstants())) {
            throw new \UnexpectedValueException('Error building the message [type rejected].');
        }
        self::$props = array('type'=>$type, 'msg'=>$msg);
        $_SESSION['msg-scoop'] = self::$props;
    }
}
