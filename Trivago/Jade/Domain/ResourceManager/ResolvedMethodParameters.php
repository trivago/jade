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

class ResolvedMethodParameters
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $remainingAttributeNames;

    /**
     * @var array
     */
    private $remainingRelationshipNames;

    /**
     * @param array $parameters
     * @param array $remainingAttributeNames
     * @param array $remainingRelationshipNames
     */
    public function __construct(array $parameters, array $remainingAttributeNames, array $remainingRelationshipNames)
    {
        $this->parameters = $parameters;
        $this->remainingAttributeNames = $remainingAttributeNames;
        $this->remainingRelationshipNames = $remainingRelationshipNames;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getRemainingAttributeNames()
    {
        return $this->remainingAttributeNames;
    }

    /**
     * @return array
     */
    public function getRemainingRelationshipNames()
    {
        return $this->remainingRelationshipNames;
    }
}
