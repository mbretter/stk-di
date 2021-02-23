<?php

namespace Stk\Service;

class OnDemand implements Injectable
{
    /** @var mixed */
    private $_instance;

    /** @var callable */
    private $_constructor;

    public function __construct(callable $constructor)
    {
        $this->_constructor = $constructor;
    }

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public function getInstance(... $args)
    {
        if ($this->_instance === null) {
            $this->_instance = call_user_func_array($this->_constructor, $args);
        }

        return $this->_instance;
    }

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public function newInstance(... $args)
    {
        return call_user_func_array($this->_constructor, $args);
    }
}
