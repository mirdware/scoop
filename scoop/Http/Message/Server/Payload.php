<?php

namespace Scoop\Http\Message\Server;

class Payload
{
    private $request;
    private $type;
    private $data;

    public function __construct($request, $type)
    {
        $this->request = $request;
        $this->type = $type;
        $this->data = array();
    }

    public function with($data)
    {
        if (empty($data)) {
            return $this;
        }
        $new = clone $this;
        $new->data = array_merge($new->data, $data);
        return $new;
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
            return $this->transform($validator->getData() + $this->data);
        }
        $errors = $validator->getErrors();
        if ($this->request->isAjax()) {
            header('HTTP/1.0 400 Bad Request');
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
        if (!$this->type) {
            return $data;
        }
        if (!class_exists($this->type)) {
            throw new \InvalidArgumentException("Type class '{$this->type}' does not exist");
        }
        return $this->createInstance($data);
    }

    private function mapConstructorParameters($parameters, $data)
    {
        $params = array();
        foreach ($parameters as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $data)) {
                $params[] = $data[$name];
            } else if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required constructor parameter: $name");
            }
        }
        return $params;
    }

    private function createInstance($data)
    {
        $reflection = new \ReflectionClass($this->type);
        $constructor = $reflection->getConstructor();
        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            return $reflection->newInstanceArgs($this->mapConstructorParameters($constructor->getParameters(), $data));
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
