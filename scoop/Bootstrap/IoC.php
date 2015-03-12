<?php
namespace Scoop\Bootstrap;

Interface IoC
{
    public function register ($key, $value);
    public function single($key);
    public function instance($key);
}