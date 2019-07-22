# stk-di

[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)


A simple dependency injection system usable with any container implementing the Psr\Container\ContainerInterface.

The service factory supports constructor injection, injection using a private method inject() and argument injection 
for services implementing the Injectable interface. 

As a special feature OnDemand services are supported, they support service creation at runtime not at creation time, 
this should avoid a dependency loading bloat for each request, due to complex dependencies. As a side effect 
OnDemand services may be used to inject objects which are not implementing the Injectable interface, like 
3rd party services.

The library comes up with a dumb container implementation, this container is mainly used for testing and 
demonstration purposes. 

## Injectable

The injectable interface is a dummy/empty interface for targeting injectable services. Services should implement 
this interface.

```php
use Stk\Service\Injectable;

class MyService implements Injectable
{
...
} 
```

## Registering services

As a first step you have to put all your services into the container and it is recommended to put the service 
factory into the container too.

```php
use Stk\Service\DumbContainer;
use Stk\Service\Factory;

class ServiceA implements Injectable
{

}

$container = new DumbContainer();
$container['config'] = [
    'param1' => 'foo',
    'param2' => 'bar'
];

$container['factory']  = new Factory($container); // put the service factory into the container
$container['serviceA'] = function (ContainerInterface $c) {
    return $c['factory']->get(ServiceA::class);
};

```

## Argument injection

The service factory scans each service for private methods having injectables as argument and injects the 
service by fetching it from the container, using the argument name as key.

```php
class ServiceA implements Injectable
{

}

class ServiceB implements Injectable
{
    protected $serviceA;

    // tell the factory to inject serviceA
    private function setServiceA(Injectable $serviceA)
    {
        $this->serviceA = $serviceA;
    }
}

$container['serviceA'] = function (ContainerInterface $c) {
    return $c['factory']->get(ServiceA::class);
};

$container['serviceB'] = function (ContainerInterface $c) {
    return $c['factory']->get(ServiceB::class);
};

$service = $this->container->get('serviceB');

```

## Constructor injection

When instantiating services, the factory scans the constructor for argument names, if they are found in the container,
the service is injected, default values are supported, if the container has no service with 
the given name. It is not needed, that the injected service implements the injectable interface, any container 
value may be injected.

```php
class ServiceC implements Injectable
{
    protected $service;
    protected $whatever;

    public function __construct($serviceA, $whatever = [])
    {
        $this->service  = $serviceA;
        $this->whatever = $whatever;
    }
}

```

### Constructor injection with params

There are some use cases, where it is needed to pass some kind of static parameters (e.g. config settings) to the 
service and some additional parameters at instantiation time.

```php
class ServiceK implements Injectable
{
    protected $config;
    public $param1;

    // $config should be passed at service declaration, $param1 at creation time
    public function __construct($config, $param1)
    {
        $this->config = $config;
        $this->param1 = $param1;
    }
}

// the container declaration for ServiceK, wrapped inside a Closure
$container['serviceK'] = function ($c) {
    return function ($param2) use ($c) {
        return $c['factory']->get(ServiceK::class, $c->get('config'), $param2);
    };
};

// accessing the service
/** @var Closure $serviceK */
$factory  = $container->get('serviceK');

/** @var ServiceK $serviceK */
$serviceK = $factory('val2');

$serviceK->param1 === 'val2';

```

## OnDemand services

OnDemand services are wrappers around services to avoid the immediate instantiation of dependend services.
In bigger projects with tons of services, it is very likely to run into a dependency bloat, ServiceA depends on
ServiceB, ServiceB needs ServiceC ... at the end you have all your services instantiated, regardeless whether 
they are used or not.

```php
class ServiceH implements Injectable
{
    public $arg1 = null;
    public $arg2 = null;

    public function __construct($arg1 = null, $arg2 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
}

class ServiceE implements Injectable
{
    /** @var OnDemand */
    public $onDemand;

    // trigger DI of OnDemand service H
    private function setService(OnDemand $serviceH)
    {
        $this->onDemand = $serviceH;
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

$container['serviceE'] = function ($c) {
    return $c['factory']->get(ServiceE::class);
};
$container['onDemandServiceH'] = function ($c) {
    // the protect method wraps the service inside into the OnDemand injectable
    return $c['factory']->protect(ServiceH::class);
};

/** @var OnDemand $serviceH */
$serviceH = $container->get('serviceH');
$inst     = $serviceH->newInstance('foo', 'bar');

// or if you want to treat them as singleton
$inst     = $serviceH->getInstance('foo', 'bar');

/** @var ServiceE $serviceE */
$serviceE = $container->get('serviceE');

$svc = $serviceE->getService();

```

### Non injectables (3rd party services)

If you want to inject services which are not implementing the Injectable interface (http-clients, etc.), 
you can register them using the OnDemand service.

```php
class ServiceJ implements Injectable
{
    /** @var OnDemand */
    protected $foreignService = null;

    private function setForeignServices(OnDemand $foreignService)
    {
        $this->foreignService = $foreignService;
    }
    
    public function getForeignService()
    {
        return $this->foreignService->getInstance();
    }
}

$container['foreignService'] = function ($c) {
    return $c['factory']->protect(stdClass::class);
};
$container['serviceJ'] = function ($c) {
    return $c['factory']->get(ServiceJ::class);
};

/** @var ServiceJ $serviceJ */
$serviceJ = $container->get('serviceJ');

$std = $serviceJ->getForeignService();

```

## Reusability with traits

Traits are very handy, if you do not want to duplicate the code when injecting the same service again and again.

Write a Trait with the property, geter and seter

```php
use Stk\Service\OnDemand;

trait DependsOnServiceB 
{
    /** @var OnDemand */
    protected $_serviceB;

    private function setServiceB(OnDemand $serviceB)
    {
        $this->_serviceB = $serviceB;

        return $this;
    }

    /**
     * @return ServiceB
     */
    protected function serviceB()
    {
        return $this->_serviceB->getInstance();
    }
}

...

class ServiceJ implements Injectable
{
    use DependsOnServiceB;
}

```