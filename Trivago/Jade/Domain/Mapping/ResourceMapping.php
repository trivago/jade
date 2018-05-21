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

namespace Trivago\Jade\Domain\Mapping;

use Trivago\Jade\Domain\Mapping\Property\Property;

class ResourceMapping
{
    /**
     * @var Property[]
     */
    private $properties = [];

    /**
     * @var Property[]
     */
    private $relationships = [];

    /**
     * @param Property $property
     */
    public function addProperty(Property $property)
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * @param string $name
     *
     * @return Property
     */
    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            throw new \InvalidArgumentException('There is no property with name '.$name);
        }
        return $this->properties[$name];
    }

    /**
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param Property $relationship
     */
    public function addRelationship(Property $relationship)
    {
        $this->relationships[$relationship->getName()] = $relationship;
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
     * @param string $name
     *
     * @return Property
     */
    public function getRelationship($name)
    {
        if (!$this->hasRelationship($name)) {
            throw new \InvalidArgumentException('There is no relationship with name '.$name);
        }
        return $this->relationships[$name];
    }

    /**
     * @return Property[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }
}
