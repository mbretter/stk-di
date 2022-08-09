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

        $container['serviceL'] = function ($c) {
            return $c['factory']->get(ServiceL::class);
        };

        $container['serviceM'] = function ($c) {
            return $c['factory']->get(ServiceM::class);
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

        $container['closure'] = function ($c) {
            return function () {
                return (object)['creator' => 'Closure'];
            };
        };

        $container['closureWithParam'] = function ($c) {
            return function ($creator) {
                return (object)['creator' => $creator];
            };
        };

        $this->container = $container;
    }

    public function testSimple(): void
    {
        $serviceA = $this->container->get('serviceA');
        $this->assertInstanceOf(ServiceA::class, $serviceA);
        $serviceB = $this->container->get('serviceB');
        $this->assertInstanceOf(ServiceB::class, $serviceB);
    }

    public function testConstructorInjection(): void
    {
        $serviceC = $this->container->get('serviceC');
        $this->assertInstanceOf(ServiceA::class, $serviceC->service);
    }

    public function testConstructorInjectionWithParams(): void
    {
        $serviceCa = $this->container->get('serviceCa');
        $this->assertInstanceOf(ServiceA::class, $serviceCa->service);
        $this->assertEquals($this->container->get('config'), $serviceCa->config);
    }

    public function testConstructorInjectionContainer(): void
    {
        $serviceJ = $this->container->get('serviceJ');
        $this->assertSame($this->container, $serviceJ->container);
    }

    public function testCombinedInjection(): void
    {
        /** @var \StkTest\Service\ServiceF $serviceF */
        $serviceF = $this->container->get('serviceF');
        $this->assertEquals($this->container->get('config'), $serviceF->config);
        $this->assertInstanceOf(ServiceC::class, $serviceF->service);
        $this->assertInstanceOf(ServiceA::class, $serviceF->getServiceA());
    }

    public function testParameterTypeInjection(): void
    {
        $serviceG = $this->container->get('serviceG');
        $this->assertInstanceOf(ServiceA::class, $serviceG->service);
    }

    public function testParameterTypeInjectionWithForeignParam(): void
    {
        $serviceG = $this->container->get('serviceG');
        $this->assertInstanceOf(ServiceA::class, $serviceG->service);
        $this->assertTrue(is_array($serviceG->whatever));
        $this->assertNull($serviceG->blackhole);
    }

    public function testWithContainerParams(): void
    {
        /** @var Closure $serviceK */
        $factory = $this->container->get('serviceK');

        /** @var ServiceK $serviceK */
        $serviceK = $factory('val2');

        $this->assertEquals($this->container->get('config'), $serviceK->config);
        $this->assertEquals('val2', $serviceK->arg2);
    }

    public function testOnDemand(): void
    {
        /** @var OnDemand $serviceH */
        $serviceH = $this->container->get('onDemandServiceH');
        $inst     = $serviceH->getInstance();
        $this->assertInstanceOf(OnDemand::class, $serviceH);
        $this->assertInstanceOf(ServiceH::class, $inst);
    }

    public function testOnDemandWithArgs(): void
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

    public function testOnDemandNewWithArgs(): void
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

    public function testOnDemandNewWithTypedArgs(): void
    {
        /** @var OnDemand $serviceI */
        $serviceI = $this->container->get('onDemandServiceI');
        $inst     = $serviceI->newInstance(true, new stdClass());
        $this->assertInstanceOf(OnDemand::class, $serviceI);
        $this->assertInstanceOf(ServiceI::class, $inst);
        $this->assertTrue($inst->arg1);
        $this->assertEquals(new stdClass(), $inst->arg2);
    }

    public function testOnDemandInject(): void
    {
        /** @var ServiceE $serviceE */
        $serviceE = $this->container->get('serviceE');

        $svc = $serviceE->getService();
        $this->assertInstanceOf(ServiceH::class, $svc);
        $this->assertSame($svc, $serviceE->getService());
        $this->assertNotSame($svc, $serviceE->newService());
    }

    public function testOnDemandForeignService(): void
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

    public function testClosureInject(): void
    {
        /** @var ServiceL $serviceL */
        $serviceL = $this->container->get('serviceL');

        $this->assertInstanceOf(Closure::class, $serviceL->closure);
        $closure = $serviceL->closure;
        $svc = $closure();
        $this->assertInstanceOf(stdClass::class, $svc);
        $this->assertEquals('Closure', $svc->creator);
    }

    public function testClosureInjectWithParam(): void
    {
        /** @var ServiceL $serviceL */
        $serviceL = $this->container->get('serviceL');

        $this->assertInstanceOf(Closure::class, $serviceL->closureWithParam);
        $closure = $serviceL->closureWithParam;
        $svc = $closure('itsme');
        $this->assertInstanceOf(stdClass::class, $svc);
        $this->assertEquals('itsme', $svc->creator);
    }

    /* union types are ignored */
    public function testUnionType(): void
    {
        /** @var ServiceM $serviceM */
        $serviceM = $this->container->get('serviceM');

        $this->assertNull($serviceM->getServiceA());
    }
}

class ServiceA implements Injectable
{

}

class ServiceB
{

}

class ServiceC implements Injectable
{
    public ServiceA $service;

    public function __construct(ServiceA $serviceA)
    {
        $this->service = $serviceA;
    }
}

class ServiceCa implements Injectable
{
    public ServiceA $service;
    public array $config;

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
    public OnDemand $onDemand;

    private function setService(OnDemand $onDemandServiceH): void
    {
        $this->onDemand = $onDemandServiceH;
    }

    public function getService(): ServiceH
    {
        return $this->onDemand->getInstance();
    }

    public function newService(): ServiceH
    {
        return $this->onDemand->newInstance();
    }
}


class ServiceF implements Injectable
{
    protected Injectable $serviceA;

    public ServiceC $service;
    public array $config;

    public function __construct(ServiceC $serviceC, array $config = [])
    {
        $this->service = $serviceC;
        $this->config  = $config;
    }

    private function setServiceA(Injectable $serviceA): void
    {
        $this->serviceA = $serviceA;
    }

    public function getServiceA(): Injectable
    {
        return $this->serviceA;
    }
}


class ServiceG
{
    public Injectable $service;
    public array $whatever;
    public ?string $blackhole = 'black';

    private function setFoo(Injectable $serviceA)
    {
        $this->service = $serviceA;
    }

    private function setBar(Injectable $serviceA, string $blackhole = null, array $whatever = []): void
    {
        $this->service   = $serviceA;
        $this->whatever  = $whatever;
        $this->blackhole = $blackhole;
    }
}


class ServiceH
{
    public ?string $arg1 = null;
    public ?string $arg2 = null;

    public function __construct(string $arg1 = null, string $arg2 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}


class ServiceI
{
    public ?bool $arg1 = null;
    public ?stdClass $arg2 = null;

    public function __construct(bool $arg1 = null, stdClass $arg2 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

class ServiceJ implements Injectable
{
    public ContainerInterface $container;

    public OnDemand $foreignService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function setForeignServices(OnDemand $foreignService)
    {
        $this->foreignService = $foreignService;
    }
}

class ServiceK implements Injectable
{
    public array $config;
    public string $arg2;

    public function __construct(array $config, string $arg2)
    {
        $this->config = $config;
        $this->arg2   = $arg2;
    }

}


class ServiceL implements Injectable
{
    public ?Closure $closure = null;

    public ?Closure $closureWithParam = null;

    private function setClosures(Closure $closure, Closure $closureWithParam)
    {
        $this->closure = $closure;
        $this->closureWithParam = $closureWithParam;
    }

}

class ServiceM implements Injectable
{
    protected ?Injectable $serviceA = null;

    private function setServiceA(Injectable|OnDemand $serviceA): void
    {
        $this->serviceA = $serviceA;
    }

    public function getServiceA(): ?Injectable
    {
        return $this->serviceA;
    }

}