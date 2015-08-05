<?php
namespace Scoop\Bootstrap;

abstract class App
{
    const MAIN_METHOD = 'get';

    public static function run()
    {
        if (substr($_SERVER['REQUEST_URI'], -9) === 'index.php') {
            \Scoop\Controller::redirect(
                str_replace('index.php', '', $_SERVER['REQUEST_URI'])
            );
        }
        
        $router = new \Scoop\IoC\Router();
        $router->register('app/routes');
        $url = '/'.filter_input(INPUT_GET, 'route', FILTER_SANITIZE_STRING);
        unset($_GET['route']);

        if (strtolower($url) !== $url) {
            throw new \Scoop\Http\NotFoundException();
        }
        // Sanear variables por POST y GET
        if ($_POST) {
            self::purgePOST($_POST);
        }
        if ($_GET) {
            self::purgeGET($_GET);
        }
        
        $controller = $router->route($url);

        if ($controller) {
            $url = str_replace($router->getURL(get_class($controller)), '', $url);
            $params = array_filter(explode('/', $url));
            unset ($url);

            if ($params) {
                $aux = explode('-', array_shift($params));

                $method = array_shift($aux);
                if (!empty($aux)) {
                    $method .= implode(array_map('ucfirst', $aux));
                }
                unset($aux);
            }

            // Normalizar el método y parámetros enviados al controlador
            $controllerReflection = new \ReflectionClass($controller);
            if (!isset($method)) {
                $method = self::MAIN_METHOD;
            } elseif ($method === self::MAIN_METHOD || !$controllerReflection->hasMethod($method)) {
                array_unshift($params, $method);
                $method = self::MAIN_METHOD;
            }
            if ($method === self::MAIN_METHOD) {
                $params = array($params);
            }
            
            $method = $controllerReflection->getMethod($method);
            $numParams = count($params);

            if ($numParams >= $method->getNumberOfRequiredParameters() && $numParams <= $method->getNumberOfParameters()) {
                unset($numParams);
                $response = $method->invokeArgs($controller, $params);
                if ($response === null) {
                    header('HTTP/1.0 204 No Response');
                } elseif ($response instanceof \Scoop\View) {
                    $response = $response->render();
                } elseif (is_array($response)) {
                    header('Content-Type: application/json');
                    $response = json_encode($response);
                }
                exit($response);
            }
        }

        throw new \Scoop\Http\NotFoundException();
    }

    private static function purgePOST(&$post)
    {
        foreach ($post as $key => $value) {
            if (is_array($value)) {
                self::purgePOST($value);
            } else {
                $post[$key] = self::filterXSS(trim($value));
            }
        }
    }

    private static function purgeGET(&$get)
    {
        foreach ($get as $key => $value) {
            if (is_array($value)) {
                self::purgeGET($value);
            } else {
                // <htmlentities> dentro del POST va a ser suprimida en proximas versiones
                $get[$key] = htmlspecialchars(trim($value) , ENT_QUOTES, 'UTF-8');
            }
        }
    }

    /**
     * Función para filtrar XSS tomada de https://gist.github.com/mbijon/1098477
     * @param string $data
     * @return Datos filtrados
     */
    private static function filterXSS($data)
    {
        // Fix &entity\n;
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);

        // we are done...
        return $data;
    }

}
