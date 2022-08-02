<?php

namespace Stk\Service;

use Closure;
use Psr\Container\ContainerInterface;
use ArrayAccess;
use Stk\Service\Exception\NotFoundException;

/**
 * dumb container implementation, mainly used for testing
 */
class DumbContainer implements ContainerInterface, ArrayAccess
{
    protected array $storage = [];

    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    public function set(string $id, mixed $value): void
    {
        $this->storage[$id] = $value;
    }

    public function has(string $id): bool
    {
        return isset($this->storage[$id]);
    }

    public function get(string $id): mixed
    {
        if (!isset($this->storage[$id])) {
            throw new NotFoundException();
        }

        $value = $this->storage[$id];
        if ($value instanceof Closure) {
            return $value($this);
        }

        return $value;
    }

    public function remove(string $id): void
    {
        if (!isset($this->storage[$id])) {
            return;
        }
        unset($this->storage[$id]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }
}
