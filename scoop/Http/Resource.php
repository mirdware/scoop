<?php
namespace Scoop\Http;

Interface Resource
{
    public function post();
    public function put($id=null);
    public function delete($id=null);
    public function get($id=null);
}
