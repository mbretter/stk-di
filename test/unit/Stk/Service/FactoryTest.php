<?php

namespace StkTest\Service;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use stdClass;
use Stk\Service\DumbContainer;
use Stk\Service\Factory;
use Stk\Service\Injectable;
use PHPUnit\Framework\TestCase;
use Stk\Service\OnDemand;

class FactoryTest extends TestCase
{
    /** @var  DumbContainer */
    protected $container;

    protected function setUp(): void
    {
        $container           = new DumbContainer();
        $container['config'] = [
            'param1' => 'foo',
            'param2' => 'bar'
        ];

        $container['factory']  = new Factory($container);
        $container['serviceA'] = function (ContainerInterface $c) {
            return $c['factory']->build(new ReflectionClass(ServiceA::class));
        };

        $container['serviceB'] = function ($c) {
            return $c['factory']->build(new ReflectionClass(ServiceB::class));
        };

        $container['serviceC'] = function ($c) {
            return $c['factory']->build(new ReflectionClass(ServiceC::class));
        };

        $container['serviceCa'] = function ($c) {
            return $c['factory']->build(new ReflectionClass(ServiceCa::class), $c['config']);
        };

        $container['serviceD'] = function ($c) {
            return $c['factory']->di($c['factory']->build(new ReflectionClass(ServiceD::class)));
        };

        $container['serviceE'] = function ($c) {
            return $c['factory']->get(ServiceE::class);
        };

        $container['serviceF'] = function ($c) {
            return $c['factory']->get(ServiceF::class);
        };

        $container['serviceG'] = function ($c) {
            return $c['factory']->get(ServiceG::class);
        };

        $container['serviceJ'] = function ($c) {
            return $c['factory']->get(ServiceJ::class);
        };

        $container['serviceK'] = function ($c) {
            return function ($param2) use ($c) {
                return $c['factory']->get(ServiceK::class, $c['config'], $param2);
            };
        };

        $container['onDemandServiceH'] = function ($c) {
            return $c['factory']->protect(ServiceH::class);
        };

        $container['onDemandServiceI'] = function ($c) {
            return $c['factory']->protect(ServiceI::class);
        };

        $container['foreignService'] = function ($c) {
            return $c['factory']->protect(stdClass::class);
        };

        $this->container = $container;
    }

    public function testSimple()
    {
        $serviceA = $this->container->get('serviceA');
        $this->assertInstanceOf(ServiceA::class, $serviceA);
        $serviceB = $this->container->get('serviceB');
        $this->assertInstanceOf(ServiceB::class, $serviceB);
    }

    public function testConstructorInjection()
    {
        $serviceC = $this->container->get('serviceC');
        $this->assertInstanceOf(ServiceA::class, $serviceC->service);
    }

    public function testConstructorInjectionWithParams()
    {
        $serviceCa = $this->container->get('serviceCa');
        $this->assertInstanceOf(ServiceA::class, $serviceCa->service);
        $this->assertEquals($this->container->get('config'), $serviceCa->config);
    }

    public function testConstructorInjectionContainer()
    {
        $serviceJ = $this->container->get('serviceJ');
        $this->assertSame($this->container, $serviceJ->container);
    }

    public function testCombinedInjection()
    {
        $serviceF = $this->container->get('serviceF');
        $this->assertEquals($this->container->get('config'), $serviceF->config);
        $this->assertInstanceOf(ServiceC::class, $serviceF->service);
        $this->assertInstanceOf(ServiceA::class, $serviceF->getServiceA());
    }

    public function testParameterTypeInjection()
    {
        $serviceG = $this->container->get('serviceG');
        $this->assertInstanceOf(ServiceA::class, $serviceG->service);
    }

    public function testParameterTypeInjectionWithForeignParam()
    {
        $serviceG = $this->container->get('serviceG');
        $this->assertInstanceOf(ServiceA::class, $serviceG->service);
        $this->assertTrue(is_array($serviceG->whatever));
        $this->assertNull($serviceG->blackhole);
    }

    public function testWithContainerParams()
    {
        /** @var Closure $serviceK */
        $factory = $this->container->get('serviceK');

        /** @var ServiceK $serviceK */
        $serviceK = $factory('val2');

        $this->assertEquals($this->container->get('config'), $serviceK->config);
        $this->assertEquals('val2', $serviceK->arg2);
    }

    public function testOnDemand()
    {
        /** @var OnDemand $serviceH */
        $serviceH = $this->container->get('onDemandServiceH');
        $inst     = $serviceH->getInstance();
        $this->assertInstanceOf(OnDemand::class, $serviceH);
        $this->assertInstanceOf(ServiceH::class, $inst);
    }

    public function testOnDemandWithArgs()
    {
        /** @var OnDemand $serviceH */
        $serviceH = $this->container->get('onDemandServiceH');
        $inst     = $serviceH->getInstance('foo', 'bar');
        $inst2    = $serviceH->getInstance('bob', 'alice');
        $this->assertInstanceOf(OnDemand::class, $serviceH);
        $this->assertInstanceOf(ServiceH::class, $inst);
        $this->assertEquals('foo', $inst->arg1);
        $this->assertEquals('bar', $inst->arg2);
        $this->assertEquals($inst, $inst2);
    }

    public function testOnDemandNewWithArgs()
    {
        /** @var OnDemand $serviceH */
        $serviceH = $this->container->get('onDemandServiceH');
        $inst     = $serviceH->newInstance('foo', 'bar');
        $inst2    = $serviceH->newInstance('bob', 'alice');
        $this->assertInstanceOf(OnDemand::class, $serviceH);
        $this->assertInstanceOf(ServiceH::class, $inst);
        $this->assertEquals('foo', $inst->arg1);
        $this->assertEquals('bar', $inst->arg2);
        $this->assertEquals('bob', $inst2->arg1);
        $this->assertEquals('alice', $inst2->arg2);
    }

    public function testOnDemandNewWithTypedArgs()
    {
        /** @var OnDemand $serviceI */
        $serviceI = $this->container->get('onDemandServiceI');
        $inst     = $serviceI->newInstance(true, new stdClass());
        $this->assertInstanceOf(OnDemand::class, $serviceI);
        $this->assertInstanceOf(ServiceI::class, $inst);
        $this->assertTrue($inst->arg1);
        $this->assertEquals(new stdClass(), $inst->arg2);
    }

    public function testOnDemandInject()
    {
        /** @var ServiceE $serviceE */
        $serviceE = $this->container->get('serviceE');

        $svc = $serviceE->getService();
        $this->assertInstanceOf(ServiceH::class, $svc);
        $this->assertSame($svc, $serviceE->getService());
        $this->assertNotSame($svc, $serviceE->newService());
    }

    public function testOnDemandForeignService()
    {
        /** @var OnDemand */
        $foreignService = $this->container->get('foreignService');

        $svc = $foreignService->getInstance();
        $this->assertInstanceOf(stdClass::class, $svc);

        /** @var ServiceJ $serviceJ */
        $serviceJ = $this->container->get('serviceJ');
        $this->assertInstanceOf(OnDemand::class, $serviceJ->foreignService);
        $this->assertInstanceOf(stdClass::class, $serviceJ->foreignService->getInstance());
    }
}

class ServiceA implements Injectable
{

}

class ServiceB
{

}

class ServiceC
{
    public $service;

    public function __construct($serviceA)
    {
        $this->service = $serviceA;
    }
}

class ServiceCa
{
    public $service;
    public $config;

    public function __construct($serviceA, $config = [])
    {
        $this->service = $serviceA;
        $this->config  = $config;
    }
}

class ServiceD
{
}

class ServiceE
{
    /** @var OnDemand */
    public $onDemand;

    private function setService(OnDemand $onDemandServiceH)
    {
        $this->onDemand = $onDemandServiceH;
    }

    public function getService()
    {
        return $this->onDemand->getInstance();
    }

    public function newService()
    {
        return $this->onDemand->newInstance();
    }
}


class ServiceF
{
    public $serviceB;
    protected $serviceA;

    public $service;
    public $config;
    public $varx;

    public function __construct($serviceC, $config = [])
    {
        $this->service = $serviceC;
        $this->config  = $config;
    }

    private function setServiceA(Injectable $serviceA)
    {
        $this->serviceA = $serviceA;
    }

    public function getServiceA()
    {
        return $this->serviceA;
    }
}


class ServiceG
{
    public $service;
    public $whatever;
    public $blackhole = 'black';

    private function setFoo(Injectable $serviceA)
    {
        $this->service = $serviceA;
    }

    private function setBar(Injectable $serviceA, $blackhole, $whatever = [])
    {
        $this->service   = $serviceA;
        $this->whatever  = $whatever;
        $this->blackhole = $blackhole;
    }

}


class ServiceH
{
    public $arg1 = null;
    public $arg2 = null;

    public function __construct($arg1 = null, $arg2 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}


class ServiceI
{
    public $arg1 = null;
    public $arg2 = null;

    public function __construct(bool $arg1 = null, stdClass $arg2 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

class ServiceJ
{
    public $container = null;
    /** @var OnDemand */
    public $foreignService = null;

    public function __construct($container)
    {
        $this->container = $container;
    }


    private function setForeignServices(OnDemand $foreignService)
    {
        $this->foreignService = $foreignService;
    }
}

class ServiceK
{
    public $config = null;
    public $arg2 = null;

    public function __construct($config, $arg2)
    {
        $this->config = $config;
        $this->arg2   = $arg2;
    }

}
