<?php

namespace Scoop\View;

class Heritage
{
    private $parent;
    private $templates;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->templates = array_merge(
            array('sdt.php' => 'Scoop\View\Template'),
            $environment->getConfig('templates', array())
        );
        ob_start();
    }

    public function getCompilePath($path)
    {
        $infoPath = pathinfo($path);
        $ext = (
            isset($infoPath['extension']) &&
            isset($this->templates[$infoPath['extension']])
        ) ? $infoPath['extension'] : 'sdt.php';
        $path = $infoPath['dirname'] . '/' . $infoPath['filename'];
        $template = \Scoop\Context::inject($this->templates[$ext]);
        return $template->parse($path);
    }

    public function setParent()
    {
        $content = ob_get_clean();
        if (isset($this->parent)) {
            $this->parent = self::parseBlocks($content, $this->parent);
        } else {
            $this->parent = $content;
        }
        ob_start();
    }

    public function getContent()
    {
        if (isset($this->parent)) {
            return self::parseBlocks(ob_get_clean(), $this->parent);
        }
        return ob_get_clean();
    }

    public static function parseBlocks($content, $parent)
    {
        $content = str_replace('$', '\$', $content);
        $res =preg_replace_callback('#@block\[(\w+)\]\s*(.*?):block#', function ($matches) use (&$parent) {
            $parent = str_replace("@slot[{$matches[1]}]", trim($matches[2]), $parent, $count);
            return $count ? '' : $matches[0];
        }, $content);
        return preg_replace('#@slot(?!\[)#', trim($res), $parent);
    }
}
