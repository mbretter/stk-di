<?php

namespace Stk\Service;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Factory
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * protected services don't get instantiated at injection time, they only will be created when used.
     * this avoids the dependency nightmare on bigger projects where practically all services are instantiated
     * whether they are used or not (dependency bloat).
     *
     * @param string $classname
     * @param mixed ...$params
     *
     * @return OnDemand
     */
    public function protect(string $classname, ...$params)
    {
        array_unshift($params, $classname);

        return new OnDemand(function (...$args) use ($params) {
            return call_user_func_array([$this, 'get'], array_merge($params, $args));
        });
    }

    /**
     * combine construct and di into one method
     *
     * @param class-string $classname
     * @param array ...$params
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function get(string $classname, ...$params)
    {
        $reflectionClass = new ReflectionClass($classname);
        array_unshift($params, $reflectionClass);
        $svc = call_user_func_array([$this, 'build'], $params);

        return $this->di($svc, $reflectionClass);
    }

    /**
     * exclude passed parameters, DI constructor injection comes before passed params
     *
     * @param ReflectionClass $reflectionClass
     * @param array ...$params
     *
     * @return object
     * @throws ReflectionException
     */
    public function build(ReflectionClass $reflectionClass, ...$params): object
    {
        $constructor = $reflectionClass->getConstructor();

        /*
         * constructor injection
         * exclude passed params from DI
         */
        if ($constructor !== null && $reflectionClass->implementsInterface(Injectable::class)) {
            $numParams  = count($params);
            $parameters = $constructor->getParameters();
            $params     = array_pad($params, -1 * $constructor->getNumberOfParameters(), null);

            for ($i = 0; $i < $constructor->getNumberOfParameters() - $numParams; $i++) {
                $param = $parameters[$i];
                if ($param->getName() == 'container') {
                    $params[$i] = $this->container;
                } else {
                    if (!$this->container->has($param->getName()) && $param->isDefaultValueAvailable()) {
                        $params[$i] = $param->getDefaultValue();
                    } else {
                        $params[$i] = $this->container->get($param->getName());
                    }
                }
            }
        }

        return $reflectionClass->newInstanceArgs($params);
    }

    /**
     * @param object $svc
     * @param ?ReflectionClass $reflectionClass
     *
     * @return object
     * @throws ReflectionException
     */
    public function di(object $svc, ReflectionClass $reflectionClass = null): object
    {
        if ($reflectionClass === null) {
            $reflectionClass = new ReflectionClass($svc);
        }

        /*
         * Injection by parameter type, parameter type must implement Stk\Service\Injectable
         * seter method must be private
         *
         * private function setFoo(\Stk\Service\Injectable $serviceA) {
         * 		$this->service = $serviceA;
         * }
         *
         */
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PRIVATE);
        foreach ($methods as $method) {

            $args  = [];
            $found = false;
            foreach ($method->getParameters() as $idx => $param) {

                $paramName = $param->getName();
                if ($param->getClass() !== null && $param->getClass()->implementsInterface(Injectable::class)) {
                    $args[] = $this->container->get($paramName);
                    $found  = true;
                } elseif ($param->getClass() !== null && $param->getClass()->getName() == Closure::class) {
                    // closure injection, but only if found in container
                    if ($this->container->has($paramName)) {
                        $args[] = $this->container->get($paramName);
                        $found  = true;
                    }
                } else {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        $args[] = null;
                    }
                }
            }

            if ($found) {
                $method->setAccessible(true);
                $method->invokeArgs($svc, $args);
            }
        }

        return $svc;
    }
}
