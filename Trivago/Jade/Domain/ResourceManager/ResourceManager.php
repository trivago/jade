<?php

/*
 * Copyright (c) 2017-present trivago GmbH
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
 */

namespace Trivago\Jade\Domain\ResourceManager;

use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;

interface ResourceManager
{
    /**
     * @param ResourceAttributeBag    $resourceAttributeBag
     * @param ResourceRelationshipBag $resourceRelationshipBag
     * @param string                  $entityClass
     *
     * @return mixed
     */
    public function create(ResourceAttributeBag $resourceAttributeBag, ResourceRelationshipBag $resourceRelationshipBag, $entityClass);

    /**
     * @param object                  $entity
     * @param ResourceAttributeBag    $resourceAttributeBag
     * @param ResourceRelationshipBag $resourceRelationshipBag
     *
     * @return mixed
     */
    public function update($entity, ResourceAttributeBag $resourceAttributeBag, ResourceRelationshipBag $resourceRelationshipBag);

    /**
     * @param object               $entity
     * @param ResourceAttributeBag $resourceAttributeBag
     */
    public function updateAttributes($entity, ResourceAttributeBag $resourceAttributeBag);

    /**
     * @param object                  $entity
     * @param ResourceRelationshipBag $resourceRelationshipBag
     */
    public function updateRelationships($entity, ResourceRelationshipBag $resourceRelationshipBag);
}
