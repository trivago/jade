<?php

/*
 * Copyright (c) 2017 trivago
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author Moein Akbarof <moein.akbarof@trivago.com>
 * @date 2017-09-10
 */

namespace Trivago\Jade\Application\JsonApi\Request;

use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;

class UpdateRequest extends CreateRequest
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @param string $resourceName
     * @param mixed $id
     * @param ResourceAttributeBag $attributes
     * @param ResourceRelationshipBag $relationships
     */
    public function __construct($resourceName, $id, ResourceAttributeBag $attributes, ResourceRelationshipBag $relationships)
    {
        parent::__construct($resourceName, $attributes, $relationships);
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
