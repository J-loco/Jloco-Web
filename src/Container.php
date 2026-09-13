<?php

declare(strict_types=1);

namespace StarLoco\Web;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal service container: shared instances, explicit factories, constructor autowiring by type.
 */
final class Container
{
    /** @var array<class-string, object> */
    private array $instances = [];

    /** @var array<class-string, Closure(self): object> */
    private array $factories = [];

    public function __construct()
    {
        $this->instances[self::class] = $this;
    }

    /** @param Closure(self): object $factory */
    public function factory(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }
        return $this->instances[$id] = $this->autowire($id);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Cannot resolve service $class");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Cannot autowire \${$parameter->getName()} of $class");
            }
        }
        return $reflection->newInstanceArgs($arguments);
    }
}
