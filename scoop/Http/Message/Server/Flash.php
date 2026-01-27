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
}
