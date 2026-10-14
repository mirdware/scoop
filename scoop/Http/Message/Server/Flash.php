<?php

namespace Scoop\Http\Message\Server;

class Flash
{
    private $referencer;

    public function __construct($referencer)
    {
        $this->referencer = $referencer;
    }

    public function set($name, $value)
    {
        self::requireActive('Flash');
        $name = explode('.', $name);
        $ref = &$_SESSION['data-scoop'];
        foreach ($name as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = array();
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
        return $this;
    }

    public function get($name)
    {
        self::requireActive('Flash');
        $name = explode('.', $name);
        $ref = $this->referencer;
        foreach ($name as $key) {
            if (!isset($ref[$key])) {
                return '';
            }
            $ref = $ref[$key];
        }
        return $ref;
    }

    private static function requireActive($feature)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \LogicException('Flash requires an active session. Enable `stateful` => true in the application context.');
        }
        if (!isset($_SESSION['data-scoop'])) {
            $_SESSION['data-scoop'] = array();
        }
    }
}
