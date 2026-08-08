<?php

namespace Scoop\Command;

class Writer
{
    private $stream = 'php://stdout';
    private $right = PHP_EOL;
    private $left = '';
    private $styles = array("\e[0m");
    private $names = array('!>');
    private $lineLength = 0;
    private $writer;
    private $isVT100;

    public function __construct($styles)
    {
        $this->isVT100 = function_exists('sapi_windows_vt100_support') ?
            sapi_windows_vt100_support(STDOUT, true) :
            strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
        if (!$this->isVT100) {
            @exec('chcp 65001 2>NUL');
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
        if (strpos($separator, "\r") !== false || strpos($separator, "\e[") !== false) {
            if ($this->left === $separator) {
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
            $this->writer->write("\r" . str_repeat(' ', $this->lineLength) . "\r");
            unset($this->writer);
        }
        $args = func_get_args();
        $std = fopen($this->stream, 'w');
        foreach ($args as $msg) {
            fwrite($std, $this->process($msg));
        }
        fflush($std);
        fclose($std);
        return $this;
    }

    public function spinner($iteration, $msg = '<link:[f]!> Loading...')
    {
        if (!isset($this->writer)) {
            $this->writer = $this->withSeparator($this->isVT100 ? "\r\e[K" : "\r");
        }
        $frames = array('⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏');
        $frame = $frames[$iteration % count($frames)];
        $output = str_replace('[f]', $frame, $msg);
        $this->lineLength = strlen($output);
        $this->writer->write($output);
    }

    public function progress($current, $total, $msg = 'Progress: <success:[f]!> [p]%')
    {
        if (!isset($this->writer)) {
            $this->writer = $this->withSeparator($this->isVT100 ? "\r\e[K" : "\r");
        }
        $percentage = round(($current / $total) * 100);
        $frames = str_repeat("█", round($percentage / 5));
        $output = str_replace(array('[f]', '[p]'), array(str_pad($frames, 60, "▒"), $percentage), $msg);
        $this->lineLength = strlen($output);
        $this->writer->write($output);
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
        $processed = str_replace($this->names, $this->styles, $msg);
        return $this->left . $processed . $this->right;
    }
}
