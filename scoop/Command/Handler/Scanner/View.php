<?php

namespace Scoop\Command\Handler\Scanner;

class View
{
    private $cleaner;
    private $writer;
    private $context;
    private $template;

    public function __construct(
        \Scoop\Command\Handler\Cleaner\View $cleaner,
        \Scoop\Command\Writer $writer,
        \Scoop\Context $context,
        \Scoop\View\Template $template
    ) {
        $this->cleaner = $cleaner;
        $this->writer = $writer;
        $this->context = $context;
        $this->template = $template;
    }

    public function execute()
    {
        $this->cleaner->execute();
        $viewPath = 'app/views/';
        \Scoop\View\Template::setPath($viewPath, $this->context->getStoragePath('cache/views'));
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewPath, \FilesystemIterator::SKIP_DOTS)
        );
        $views = new \RegexIterator($iterator, '/\.sdt\.php$/');
        foreach ($views as $view) {
            if ($view->isFile()) {
                $path = str_replace('\\', '/', $view->getPathname());
                $name = substr($path, strlen($viewPath), -8);
                $this->template->parse($name);
                $this->writer->write('<link:' . $path . '!> ... <success:created!>');
            }
        }
        $this->writer->write('<done:scan finished!!>');
    }

    public function help()
    {
        $this->writer->write(
            'Scan view folder for create cache of production.'
        );
    }
}
