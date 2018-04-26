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

namespace Trivago\Jade\Domain\ResourceManager\Bag;

class ToManyRelationship extends Relationship
{
    /**
     * @var array
     */
    private $entityIds;

    /**
     * @param string $targetResourceName
     * @param string $entityClass
     * @param array $entityIds
     */
    public function __construct($targetResourceName, $entityClass, array $entityIds)
    {
        parent::__construct($targetResourceName, $entityClass);
        $this->entityIds = $entityIds;
    }

    /**
     * @return array
     */
    public function getEntityIds()
    {
        return $this->entityIds;
    }
}
