<?php
namespace Scoop\Http;

Interface Resource
{
    public function post();
    public function get($id = null);
}
