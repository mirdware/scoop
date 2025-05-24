<?php

namespace Scoop\Command;

class Directory
{
    public function delete($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $items = scandir($dir);
        if (!$items) {
            return false;
        }
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    if (!$this->delete($path)) {
                        return false;
                    }
                } elseif (!unlink($path)) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }
}