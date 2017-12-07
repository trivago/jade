<?php

namespace Trivago\Jade\Domain\ResourceManager\Bag;

class NullRelationship extends Relationship
{
    public function __construct()
    {
        parent::__construct(null, null);
    }
}
