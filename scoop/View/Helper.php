<?php
namespace Scoop\View;

class Helper
{
    private $name;
    private $msg;
    private $assets;
    private $router;

    public function __construct($name, $msg)
    {
        $config = \Scoop\IoC\Service::getInstance('config');
        $this->name = $name;
        $this->msg = $msg;
        $this->router = $config->getRouter();
        $this->assets = (array) $config->get('asset') + array(
            'path' => 'public/',
            'img' => 'images/',
            'css' => 'css/',
            'js' => 'js/'
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function overt($resource)
    {
        return ROOT.$this->assets['path'].$resource;
    }

    public function img($image)
    {
        return $this->overt($this->assets['img'].$image);
    }

    public function css($styleSheet)
    {
        return $this->overt($this->assets['css'].$styleSheet);
    }

    public function js($javaScript)
    {
        return $this->overt($this->assets['js'].$javaScript);
    }

    public function route()
    {
        if (func_num_args() === 0) {
            throw new \Exception('Unsoported number of arguments');
        }
        $args = func_get_args();
        return ROOT.$this->router->getURL(array_shift($args), $args);
    }
}
