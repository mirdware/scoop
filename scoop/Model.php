<?php
namespace Scoop;

interface Model
{
    public static function getFilter();
    public static function get();
    public static function getList();
    public function fromArray($array);
    public function getPK();
    public function persist();
    public function remove();
}
