<?php

namespace Scoop\Http\Message;

class Route
{
    private $id;
    private $variables;
    private $query;

    public function __construct($id)
    {
        $this->id = $id;
        $this->variables = array();
        $this->query = array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function getVariable($name)
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        }
        return null;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function withVariables($variables)
    {
        $new = clone $this;
        $new->variables += $variables;
        return $new;
    }

    public function withQuery($query)
    {
        $new = clone $this;
        $new->query += $query;
        return $new;
    }

    public function flash($name, $value)
    {
        $name = explode('.', $name);
        $ref = &$_SESSION['data-scoop'];
        foreach ($name as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = array();
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
        return $this;
    }

    public function getURL(\Scoop\Http\Router $router)
    {
        return $router->getURL($this->id, $this->variables, $this->query);
    }
}
