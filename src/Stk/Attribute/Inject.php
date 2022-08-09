<?php

namespace Stk\Attribute;

use Stk\Service\Injectable;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Inject implements Injectable
{
    /* the container id/key */
    public ?string $id;

    /* the property name */
    public ?string $prop;

    public function __construct(?string $id = null, ?string $prop = null)
    {
        $this->id   = $id;
        $this->prop = $prop;
    }
}