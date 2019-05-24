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
    /**
     * @var mixed[]
     */
    protected $storage = [];

    /**
     * @param mixed[] $storage
     */
    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $id, $value): void
    {
        $this->storage[$id] = $value;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->storage[$id]);
    }

    /**
     * @param string $id
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id)
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

    /**
     * {@inheritDoc}
     */
    public function remove(string $id): void
    {
        if (!isset($this->storage[$id])) {
            return;
        }
        unset($this->storage[$id]);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
