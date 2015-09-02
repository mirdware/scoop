<?php
namespace Scoop\View;

final class Message
{
    const OUT = 'out';
    const ERROR = 'error';
    const WARNING = 'warning';
    private $msg;

    public function __construct()
    {
        $this->msg = '<div id="msg-not"></div>';
    }

    public function push($msg, $type)
    {
        self::validate($type);
        $_SESSION['msg-scoop'] = array('type'=>$type, 'msg'=>$msg);
    }

    public function pull()
    {
        if (isset($_SESSION['msg-scoop'])) {
            $this->setMsg($_SESSION['msg-scoop']['type'], $_SESSION['msg-scoop']['msg']);
            unset($_SESSION['msg-scoop']);
        }
    }

    public function set($msg, $type)
    {
        self::validate($type);
        $this->setMsg($type, $msg);
    }

    public function __toString()
    {
        return $this->msg;
    }

    private function setMsg($type, $msg)
    {
        $this->msg = '<div id="msg-'.$type.'">'.$msg.'</div>';
    }

    private static function validate($type)
    {
        if ($type !== self::OUT && $type !== self::ERROR && $type !== self::WARNING) {
            throw new \Exception('Error building only accepted message types: out, warning and error.');
        }
    }
}
