<?php

namespace Test;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class Architecture
{
    private $namespaces;

    public function __construct($contextFile = null)
    {
        $this->namespaces = $this->boundedNamespaces($contextFile ? $contextFile : dirname(__DIR__) . '/app/context.php');
    }

    public function testBoundedIsolation(): array
    {
        $rules = array();
        foreach ($this->namespaces as $namespace) {
            foreach ($this->namespaces as $other) {
                if ($namespace !== $other) {
                    $rules[$namespace . ' -> ' . $other] = PHPat::rule()
                        ->classes(Selector::inNamespace($namespace))
                        ->shouldNot()->dependOn()
                        ->classes(Selector::inNamespace($other));
                }
            }
        }
        return $rules;
    }

    public function testLayerDependencies(): array
    {
        $rules = array();
        foreach ($this->namespaces as $namespace) {
            $rules[$namespace . '\\Domain'] = PHPat::rule()
                ->classes(Selector::inNamespace($namespace . '\\Domain'))
                ->shouldNot()->dependOn()
                ->classes(
                    Selector::inNamespace($namespace . '\\Application'),
                    Selector::inNamespace($namespace . '\\Infrastructure')
                );
            $rules[$namespace . '\\Application'] = PHPat::rule()
                ->classes(Selector::inNamespace($namespace . '\\Application'))
                ->shouldNot()->dependOn()
                ->classes(Selector::inNamespace($namespace . '\\Infrastructure'));
        }
        return $rules;
    }

    public function testProductionDoesNotDependOnTests(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('Test'), Selector::inNamespace('PHPUnit'));
    }

    private function boundedNamespaces($contextFile): array
    {
        if (!is_file($contextFile)) {
            return array();
        }
        $context = require $contextFile;
        if (!is_array($context)) {
            throw new \UnexpectedValueException('app/context.php must return an array.');
        }
        if (!array_key_exists('bounded', $context)) {
            return array();
        }
        if (!is_array($context['bounded'])) {
            throw new \UnexpectedValueException('bounded must be a namespace => directory map.');
        }
        $namespaces = array();
        foreach ($context['bounded'] as $namespace => $directory) {
            if (!is_string($namespace) || !preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*\\\\?$/D', $namespace)) {
                throw new \UnexpectedValueException('Each bounded key must be a namespace.');
            }
            if (!is_string($directory) || $directory === '') {
                throw new \UnexpectedValueException('Each bounded directory must be a non-empty string.');
            }
            $namespace = rtrim($namespace, '\\');
            foreach ($namespaces as $existing) {
                if (stripos($namespace . '\\', $existing . '\\') === 0 || stripos($existing . '\\', $namespace . '\\') === 0) {
                    throw new \UnexpectedValueException('Bounded namespaces must not overlap: ' . $namespace);
                }
            }
            $namespaces[] = $namespace;
        }
        return $namespaces;
    }
}
