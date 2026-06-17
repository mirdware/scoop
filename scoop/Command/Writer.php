<?php

namespace Scoop\Command;

class Writer
{
    const CLEAR = "\r\e[2K";
    private $stream = 'php://stdout';
    private $right = PHP_EOL;
    private $left = '';
    private $styles = array("\e[0m");
    private $names = array('!>');
    private $writer;

    public function __construct($styles)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('chcp 65001 2>NUL');
        }
        foreach ($styles as $name => $style) {
            array_push($this->names, "<$name:");
            array_push($this->styles, "\e[" . implode(';', $style) . 'm');
        }
    }

    public function withStyle()
    {
        $styles = func_get_args();
        $name = array_shift($styles);
        if (empty($styles)) {
            throw new \InvalidArgumentException('Style must be specified');
        }
        $new = clone $this;
        $index = array_search("<$name:", $this->names);
        if ($index !== false) {
            $new->styles[$index] = "\e[" . implode(';', $styles) . 'm';
        } else {
            array_push($new->names, "<$name:");
            array_push($new->styles, "\e[" . implode(';', $styles) . 'm');
        }
        return $new;
    }

    public function withError()
    {
        if ($this->stream === 'php://stderr') {
            return $this;
        }
        $new = clone $this;
        $new->stream = 'php://stderr';
        return $new;
    }

    public function withSeparator($separator)
    {
        $right = $separator;
        $left = '';
        if ($separator === self::CLEAR) {
            if ($this->left === self::CLEAR) {
                return $this;
            }
            $left = $separator;
            $right = '';
        } elseif ($this->right === $separator) {
            return $this;
        }
        $new = clone $this;
        $new->left = $left;
        $new->right = $right;
        return $new;
    }

    public function write()
    {
        if (isset($this->writer)) {
            $this->writer->write('');
            unset($this->writer);
        }
        $args = func_get_args();
        $std = fopen($this->stream, 'w');
        foreach ($args as $msg) {
            fwrite($std, $this->process($msg));
        }
        fclose($std);
        return $this;
    }

    public function spinner($iteration, $theme = 'link', $msg = 'Loading...') {
        if (!isset($this->writer)) {
            $this->writer = $this->withSeparator(self::CLEAR);
        }
        $frames = array('⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏');
        $frame = $frames[$iteration % count($frames)];
        $this->writer->write("<$theme:$frame!> $msg");
    }

    public function progress($current, $total, $theme = 'success', $label = 'Progress:') {
        if (!$this->writer) {
            $this->writer = $this->withSeparator(self::CLEAR);
        }
        $percentage = round(($current / $total) * 100);
        $frames = str_repeat("█", $percentage / 5);
        $this->writer->write("$label <$theme:" . str_pad($frames, 60, "▒") . "!> $percentage%");
    }

    public function input($prompt, $hidden = false)
    {
        $this->write($prompt . ($hidden ? "\e[8m" : ''));
        $std = fopen('php://stdin', 'r');
        $input = fgets($std);
        fclose($std);
        if ($hidden) {
            $this->write("!>");
        }
        return trim($input);
    }

    public function process($msg)
    {
        return $this->left . str_replace($this->names, $this->styles, $msg) . $this->right;
    }
}
