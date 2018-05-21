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

class ResourceAttributeBag
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $attributeName
     *
     * @return mixed
     */
    public function getValue($attributeName)
    {
        if (!array_key_exists($attributeName, $this->attributes)) {
            throw new \InvalidArgumentException('No attribute found with name '.$attributeName);
        }
        return $this->attributes[$attributeName];
    }

    /**
     * @return array
     */
    public function getAllAttributeNames()
    {
        return array_keys($this->attributes);
    }
}
