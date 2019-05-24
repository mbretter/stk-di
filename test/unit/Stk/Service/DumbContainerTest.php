<?php

namespace StkTest\Service;

use Stk\Service\DumbContainer;
use PHPUnit\Framework\TestCase;
use Stk\Service\Exception\NotFoundException;

class DumbContainerTest extends TestCase
{
    /** @var  DumbContainer */
    protected $container;

    public function setUp()
    {
        $this->container = new DumbContainer();
    }

    public function testSetGet()
    {
        $this->container->set('foo', 'bar');
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    /**
     * @expectedException \Stk\Service\Exception\NotFoundException
     *
     * @throws NotFoundException
     */
    public function testGetNotFound()
    {
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    public function testRemove()
    {
        $this->container->set('foo', 'bar');
        $this->container->remove('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testRemoveNotExisting()
    {
        $this->container->remove('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testArrayGet()
    {
        $this->container->set('foo', 'bar');
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testArraySet()
    {
        $this->container['foo'] = 'bar';
        $this->assertEquals('bar', $this->container['foo']);
    }

    public function testArrayRemove()
    {
        $this->container->set('foo', 'bar');
        unset($this->container['foo']);
        $this->assertFalse($this->container->has('foo'));
    }

    public function testArrayExists()
    {
        $this->container->set('foo', 'bar');
        $this->assertTrue(isset($this->container['foo']));
    }
}