<?php

namespace Scoop\Command\Factory;

class Writer
{
    public function create()
    {
        return new \Scoop\Command\Writer(
            \Scoop\Context::getEnvironment()->getConfig('ice.styles', array() + array(
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
