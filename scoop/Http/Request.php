<?php
namespace Scoop\Http;

class Request
{
    private static $put;
    private static $post;
    private static $get;

    public function __construct()
    {
        self::$get = self::purge($_GET);
        self::$post = self::purge($_POST);
        self::$put = array();
        $datosPUT = fopen("php://input", "r");
        while ($datos = fread($datosPUT, 1024)) {
            $datos = explode('=', $datos);
            self::$put[$datos[0]] = self::purge($datos[1]);
        }
    }

    public function get($id = null)
    {
        return self::getByIndex($id, self::$get);
    }

    public function post($id = null)
    {
        return self::getByIndex($id, self::$post);
    }

    public function put($id = null)
    {
        return self::getByIndex($id, self::$put);
    }

    private static function getByIndex($name, $res)
    {
        if (!$name) {
            return $res;
        }
        $name = explode('.', $name);
        foreach ($name as $key) {
            if (!isset($res[$key])) {
                return '';
            }
            $res = $res[$key];
        }
        return $res;
    }

    private static function purge($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => &$v) {
                $value[$key] = self::purge($v);
            }
            return $value;
        }
        $value = self::filterXSS(trim($value));
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return $value;
}

    /**
     * Método para filtrar XSS tomado de https://gist.github.com/mbijon/1098477
     * @param string $data Datos en crudo, tal cual lo ingreso el usuario
     * @return string Datos filtrados
     */
    private static function filterXSS($data)
    {
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
        do {
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);
        return $data;
    }
}