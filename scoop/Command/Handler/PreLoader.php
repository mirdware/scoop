<?php

namespace Scoop\Command\Handler;

class PreLoader
{
    private $writer;
    private $environment;

    public function __construct(
        \Scoop\Command\Writer $writer,
        \Scoop\Bootstrap\Environment $environment
    ) {
        $this->writer = $writer
        ->withStyle('quote', \Scoop\Command\Style\Color::CYAN)
        ->withStyle('number', \Scoop\Command\Style\Color::MAGENTA);
        $this->environment = $environment;
    }

    public function execute($command)
    {
        $args = $command->getArguments();
        $res = $this->environment->loadLazily($args[0]);
        if ($command->hasFlag('v')) {
            $res = var_export($res, true);
            $res = preg_replace_callback('/(\'[^\']*\')|(\d+)|(\b(true|false|NULL)\b)/', function ($matches) {
                if ($matches[1] !== '') {
                    return '<quote:' . $matches[1] . '!>';
                }
                if ($matches[2] !== '') {
                    return '<number:' . $matches[2] . '!>';
                }
                if ($matches[3] !== '') {
                    return '<link:' . $matches[3] . '!>';
                }
                return $matches[0];
            }, $res);
            $this->writer->write($res);
        }
        $this->writer->write("✨<warn:{$args[0]}!> loaded <success:successfully!>.");
    }

    public function help()
    {
        $this->writer->write(
            'Simulates loading a configuration system file.',
            '',
            'Arguments:',
            'File to load in format type:file'
        );
    }
}
