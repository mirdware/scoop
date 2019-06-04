<?php
namespace Scoop\Http;

class Request
{
    private static $body;
    private static $query;
    private static $refererData;

    public function __construct()
    {
        self::$body = self::getBodyData();
        self::$query = self::purge($_GET);
        self::$refererData = array();
        foreach ($_FILES AS $name => $file) {
            self::$body[$name] = $file;
        }
        if (isset($_SESSION['data-scoop'])) {
            self::$refererData = $_SESSION['data-scoop'];
            unset($_SESSION['data-scoop']);
        }
    }

    public function getQuery($id = null) 
    {
        return self::getByIndex($id, self::$query);
    }

    public function getBody($id = null) 
    {
        return self::getByIndex($id, self::$body);
    }

    public function reference($id)
    {
        return self::getByIndex($id, self::$refererData);
    }

    /**
     * Verifica si la pagina fue llamada via ajax o normalmente.
     * @return boolean Devuelve true si la página fue llamada via ajax y false
     * en caso contrario.
     */
    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    private static function getByIndex($name, $res)
    {
        if (!$name) return $res;
        $name = explode('.', $name);
        foreach ($name as $key) {
            if (!isset($res[$key])) return '';
            $res = $res[$key];
        }
        return $res;
    }

    private static function purge($array)
    {
        foreach ($array as $key => $value) {
            $array[$key] = is_array($value) ?
            self::purge($value) :
            self::filterXSS($value);
        }
        return $array;
    }

    private static function getBodyData()
    {
        $data = file_get_contents("php://input");
        $put = array();
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $data = json_decode($data, true);
            return self::purge($data);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') return self::purge($_POST);
        if (!$data) return $put;
        $data = explode('&', $data);
        foreach ($data as $value) {
            $value = explode('=', $value);
            $put[$value[0]] = urldecode($value[1]);
        }
        return $put;
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
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
