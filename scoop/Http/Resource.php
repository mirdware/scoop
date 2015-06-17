<?php
namespace Scoop\Http;

Interface Resource
{
	public function post();
	public function put(array $args);
	public function delete(array $args);
}
