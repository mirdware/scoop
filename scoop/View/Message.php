<?php

namespace Scoop\View;

/**
 * Clase componente de los mensajes usados por el Bootstrap.
 */
class Message
{
    const INFO = 'info';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    private $request;

    public function __construct(\Scoop\Http\Message\Server\Request $request)
    {
        $this->request = $request;
    }

    /**
     * Crea el componente en la vista.
     * @return string Renderiza el mensaje.
     */
    public function render()
    {
        $message = (array) $this->request->flash('message') + array('type' => 'not', 'text' => '');
        return
<<<HTML
<div id="msg" data-attr="className:type" class="{$message['type']}">
    <i class="close"></i>
    <span data-bind="msg">{$message['text']}</span>
</div>
HTML;
    }
}
