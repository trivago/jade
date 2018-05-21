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

class ResourceRelationshipBag
{
    /**
     * @var Relationship[]
     */
    private $relationships;

    /**
     * @param Relationship[] $relationships
     */
    public function __construct(array $relationships)
    {
        $this->relationships = $relationships;
    }

    /**
     * @param string $relationshipName
     *
     * @return Relationship
     */
    public function getValue($relationshipName)
    {
        if (!$this->hasRelationship($relationshipName)) {
            throw new \InvalidArgumentException('No relationship found with name '.$relationshipName);
        }
        return $this->relationships[$relationshipName];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasRelationship($name)
    {
        return array_key_exists($name, $this->relationships);
    }

    /**
     * @return array
     */
    public function getAllRelationshipNames()
    {
        return array_keys($this->relationships);
    }

    /**
     * @return Relationship[]
     */
    public function getAll()
    {
        return $this->relationships;
    }
}
