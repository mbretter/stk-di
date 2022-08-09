<?php

namespace StkTest\Service;

use Stk\Service\DumbContainer;
use Stk\Service\Factory;
use Stk\Service\Injectable;
use PHPUnit\Framework\TestCase;
use Stk\Attribute\Inject;
use Stk\Service\OnDemand;

class FactoryAttributeTest extends TestCase
{
    protected DumbContainer $container;

    protected function setUp(): void
    {
        $container           = new DumbContainer();
        $container['config'] = [
            'param1' => 'foo',
            'param2' => 'bar'
        ];

        $container['factory']  = new Factory($container);
        $container['serviceA'] = function ($c) {
            return $c['factory']->get(AttrServiceA::class);
        };

        $container['serviceADemand'] = function ($c) {
            return $c['factory']->protect(AttrServiceA::class);
        };

        $container['serviceB'] = function ($c) {
            return $c['factory']->get(AttrServiceB::class);
        };

        $container['serviceC'] = function ($c) {
            return $c['factory']->get(AttrServiceC::class);
        };

        $container['serviceD'] = function ($c) {
            return $c['factory']->get(AttrServiceD::class);
        };

        $container['serviceE'] = function ($c) {
            return $c['factory']->get(AttrServiceE::class);
        };

        $this->container = $container;
    }

    public function testPropertyAttributes(): void
    {
        /** @var AttrServiceC $serviceC */
        $serviceC = $this->container->get('serviceC');
        $this->assertInstanceOf(AttrServiceA::class, $serviceC->getServiceA());
        $this->assertInstanceOf(AttrServiceB::class, $serviceC->getServiceB());
    }

    public function testClassAttributes(): void
    {
        /** @var AttrServiceD $serviceD */
        $serviceD = $this->container->get('serviceD');
        $this->assertInstanceOf(AttrServiceA::class, $serviceD->getServiceA());
        $this->assertInstanceOf(AttrServiceB::class, $serviceD->getServiceB());
    }

    public function testOnDemand(): void
    {
        /** @var AttrServiceE $serviceE */
        $serviceE = $this->container->get('serviceE');
        $this->assertInstanceOf(AttrServiceA::class, $serviceE->getServiceA());
    }

}

class AttrServiceA implements Injectable
{

}

class AttrServiceB
{

}

class AttrServiceC implements Injectable
{
    #[Inject(id: "serviceA")]
    private AttrServiceA $serviceA;

    #[Inject]
    private AttrServiceB $serviceB;

    public function getServiceA(): AttrServiceA
    {
        return $this->serviceA;
    }

    public function getServiceB(): AttrServiceB
    {
        return $this->serviceB;
    }
}

#[Inject] // will be ignored
#[Inject(id: "serviceA")]
#[Inject(id: "serviceB", prop: "myService")]
class AttrServiceD implements Injectable
{
    private AttrServiceA $serviceA;

    private AttrServiceB $myService;

    public function getServiceA(): AttrServiceA
    {
        return $this->serviceA;
    }

    public function getServiceB(): AttrServiceB
    {
        return $this->myService;
    }
}

class AttrServiceE implements Injectable
{
    #[Inject]
    private OnDemand $serviceADemand;

    public function getServiceA(): AttrServiceA
    {
        return $this->serviceADemand->getInstance();
    }

}

