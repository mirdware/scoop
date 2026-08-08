<?php

namespace Scoop\Bootstrap\Scanner\Source;

class Tokenizer
{
    private $stream;
    private $threshold;
    private $buffer = '';

    public function __construct($stream, $threshold = 4096)
    {
        $this->stream = $stream;
        $this->threshold = $threshold;
    }

    public function tokenize()
    {
        $finished = feof($this->stream);
        if ($finished) {
            return false;
        }
        $previousLength = strlen($this->buffer);
        while (strlen($this->buffer) < $this->threshold && !$finished) {
            $length = $this->threshold - strlen($this->buffer);
            $chunk = fread($this->stream, $length);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $this->buffer .= $chunk;
            $finished = feof($this->stream);
        }
        if (strlen($this->buffer) === $previousLength) {
            return false;
        }
        $tokens = token_get_all($this->buffer);
        if (!$finished) {
            $this->threshold *= 2;
        }
        return $tokens;
    }
}
