<?php

namespace Scoop\Http;

class Request
{
    private $body;
    private $query;
    private $referrer;
    private $url;

    public function __construct($data = array())
    {
        $this->url = isset($data['url']) ? $data['url'] : $this->setURL();
        $this->body = isset($data['body']) ? $data['body'] : $this->setBody();
        $this->query = isset($data['query']) ? $data['query'] : $this->purge($_GET);
        $this->referrer = isset($data['referrer']) ? $data['referrer'] : $this->setReferrer();
    }

    public function getQuery($id = null)
    {
        return $this->getByIndex($id, $this->query);
    }

    public function getBody($id = null)
    {
        return $this->getByIndex($id, $this->body);
    }

    public function getURL()
    {
        return $this->url;
    }

    public function reference($id)
    {
        return $this->getByIndex($id, $this->referrer);
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

    private function getByIndex($name, $res)
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

    private function purge($array)
    {
        foreach ($array as $key => $value) {
            $array[$key] = is_array($value) ? $this->purge($value) : $this->filterXSS($value);
        }
        return $array;
    }

    private function setURL()
    {
        if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
            \Scoop\Controller::redirect(
                str_replace('index.php', '', $_SERVER['REQUEST_URI']),
                301
            );
        }
        $url = '/';
        if (isset($_GET['route'])) {
            $url .= filter_var($_GET['route'], FILTER_SANITIZE_URL);
            unset($_GET['route'], $_REQUEST['route']);
        }
        return $url;
    }

    private function setReferrer()
    {
        $referrer = isset($_SESSION['data-scoop']) ? $_SESSION['data-scoop'] : array();
        if (!$this->isAjax()) {
            $_SESSION['data-scoop'] = array(
                'http' => substr(ROOT, 0, strpos(ROOT, '/', 7)) . $_SERVER['REQUEST_URI']
            );
        }
        return $referrer;
    }

    private function setBody()
    {
        $data = file_get_contents('php://input');
        $body = array();
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $data = json_decode($data, true);
            return $data ? $this->purge($data) : array();
        }
        foreach ($_FILES as $name => $file) {
            $body[$name] = $file;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $body + $this->purge($_POST);
        }
        if (!$data) {
            return $body;
        }
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];
        $blocks = preg_split("/-+$boundary/", $data);
        array_pop($blocks);
        foreach ($blocks as $block) {
            if (!empty($block)) {
                $regex = strpos($block, 'application/') ?
                "/name=\"([^\"]*)\"[^\n]*[\n|\r]+([^\n\r].*)?$/s" :
                '/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s';
                preg_match($regex, $block, $matches);
                $body[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
            }
        }
        return $body;
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
