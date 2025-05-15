<?php

namespace Scoop\Http\Message\Server;

class Payload
{
    private $request;
    private $type;

    public function __construct($request, $type)
    {
        $this->request = $request;
        $this->type = $type;
    }

    public function fromBody(\Scoop\Validator $validator)
    {
        return $this->validate($validator, $this->request->getParsedBody());
    }

    public function fromQuery(\Scoop\Validator $validator)
    {
        return $this->validate($validator, $this->request->getQueryParams());
    }

    private function validate($validator, $data)
    {
        if ($validator->validate($data)) {
            return $this->transform($validator->getData());
        }
        $errors = $validator->getErrors();
        $contentType = $this->request->getHeaderLine('Accept');
        header('HTTP/1.0 400 Bad Request');
        if (strpos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            exit (json_encode(array('code' => 400, 'message' => $errors)));
        }
        $_SESSION['data-scoop'] += array(
            'body' => $this->request->getParsedBody(),
            'query' => $this->request->getQueryParams(),
            'error' => $errors
        );
        $this->request->goBack();
    }

    private function transform($data)
    {
        if (!$data || !$this->type || !is_array($data)) {
            return $data;
        }
        if (!class_exists($this->type)) {
            throw new \InvalidArgumentException("Type class '{$this->type}' does not exist");
        }
        return $this->createInstance($data);
    }

    private function mapConstructorParameters($constructor, $data)
    {
        $params = array();
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $data)) {
                $params[] = $data[$name];
            } else if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required constructor parameter: {$name}");
            }
        }
        return $params;
    }

    private function createInstance($data)
    {
        if (is_object($data) && get_class($data) === $this->type) {
            return $data;
        }
        $reflection = new \ReflectionClass($this->type);
        $constructor = $reflection->getConstructor();
        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            return $reflection->newInstanceArgs($this->mapConstructorParameters($constructor, $data));
        }
        if (is_array($data)) {
            $instance = $reflection->newInstance();
            foreach ($data as $property => $value) {
                if ($reflection->hasProperty($property)) {
                    $prop = $reflection->getProperty($property);
                    $prop->setAccessible(true);
                    $prop->setValue($instance, $value);
                }
            }
            return $instance;
        }
        throw new \InvalidArgumentException("Unable to create instance of '{$this->type}' from provided data");
    }
}
