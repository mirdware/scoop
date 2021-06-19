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
    private static $request;

    /**
     * Crea el componente en la vista.
     * @return string Renderiza el mensaje.
     */
    public function render()
    {
        if (self::$props) {
            unset($_SESSION['data-scoop']['message']);
        } else {
            $message = self::$request->reference('message');
            self::$props = $message ? $message : array('type' => 'not', 'msg' => '');
        }
        $type = self::$props['type'];
        $msg = self::$props['msg'];
        return '
        <div id="msg" data-attr="className:type" class="'.$type.'">
            <i class="close"></i>
            <span data-bind="msg">'.$msg.'</span>
        </div>';
    }

    public static function setRequest(\Scoop\Http\Request $request)
    {
        self::$request = $request;
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
            throw new \UnexpectedValueException('Error building the message [type '.$type.' rejected].');
        }
        self::$props = array('type' => $type, 'msg' => $msg);
        $_SESSION['data-scoop']['message'] = self::$props;
    }
}
