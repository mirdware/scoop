<?php
namespace Scoop\Container;

abstract class Injector
{
    private $rules = array();

    public function __construct($environment)
    {
        $this->bind((Array) $environment->getConfig('providers'));
    }

    public static function formatClassName($className)
    {
        if (strpos($className, '\\') === 0) return substr($className, 1);
        return $className;
    }

    public abstract function has($id);

    protected abstract function getInstance($id);

    protected abstract function setInstance($id, $instance);

    public function get($id)
    {
        $id = self::formatClassName($id);
        if (!$this->has($id)) {
            if (isset($this->rules[$id])) {
                return $this->get($this->rules[$id]);
            }
            $this->setInstance($id, $this->create($id));
        }
        return $this->getInstance($id);
    }

    public function create($className, $args = array())
    {
        try {
            $class = new \ReflectionClass($className);
            if (!$class->isInstantiable()) {
                throw new \Exception('Cannot inject '.$className.' because it cannot be instantiated');
            }
            $constructor = $class->getConstructor();
            if ($constructor) {
                $args = array_merge($this->getArguments($constructor->getParameters()), $args);
                return $class->newInstanceArgs($args);
            }
            return $class->newInstanceWithoutConstructor();
        } catch (\ReflectionException $ex) {
            throw new \Scoop\Http\NotFoundException($className.' not found', $ex);
        }
    }

    private function bind($interfaces)
    {
        foreach ($interfaces as $interfaceName => $className) {
            $interfaceName = self::formatClassName($interfaceName);
            $className = self::formatClassName($className);
            $this->rules[$interfaceName] = $className;
        }
    }

    private function getArguments($params)
    {
        $args = array();
        foreach ($params as $param) {
            $class = method_exists($param, 'getType') ? $param->getType() : $param->getClass();
            if ($class) {
                $args[] = $this->get($class->getName());
            }
        }
        return $args;
    }
}
