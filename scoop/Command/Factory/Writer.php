<?php

namespace Scoop\Command\Factory;

class Writer
{
    private $environment;

    public function __construct(\Scoop\Bootstrap\Environment $environment)
    {
        $this->environment = $environment;
    }

    public function create()
    {
        return new \Scoop\Command\Writer(
            $this->environment->getConfig('ice.styles', array() + array(
                'link' => array(\Scoop\Command\Style\Color::BLUE),
                'error' => array(\Scoop\Command\Style\Color::RED),
                'alert' => array(\Scoop\Command\Style\Color::YELLOW),
                'success' => array( \Scoop\Command\Style\Color::GREEN),
                'info' => array(\Scoop\Command\Style\Background::BLUE),
                'fail' => array(\Scoop\Command\Style\Background::RED),
                'done' => array(\Scoop\Command\Style\Background::GREEN),
                'warning' => array(\Scoop\Command\Style\Background::YELLOW),
                'high' => array(\Scoop\Command\Style\Color::YELLOW, \Scoop\Command\Style\Format::BOLD)
            ))
        );
    }
}
