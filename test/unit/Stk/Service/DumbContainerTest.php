<?php

namespace StkTest\Service;

use Stk\Service\DumbContainer;
use PHPUnit\Framework\TestCase;
use Stk\Service\Exception\NotFoundException;

class DumbContainerTest extends TestCase
{
    protected DumbContainer $container;

    protected function setUp(): void
    {
        $this->container = new DumbContainer();
    }

    public function testSetGet(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    public function testRemove(): void
    {
        $this->container->set('foo', 'bar');
        $this->container->remove('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testRemoveNotExisting(): void
    {
        $this->container->remove('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testArrayGet(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testArraySet(): void
    {
        $this->container['foo'] = 'bar';
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testArrayRemove(): void
    {
        $this->container->set('foo', 'bar');
        unset($this->container['foo']);
        $this->assertFalse($this->container->has('foo'));
    }

    public function testArrayExists(): void
    {
        $this->container->set('foo', 'bar');
        $this->assertTrue(isset($this->container['foo']));
    }
}