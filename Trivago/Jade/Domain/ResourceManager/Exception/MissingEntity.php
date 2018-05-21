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

namespace Trivago\Jade\Domain\ResourceManager\Exception;

class MissingEntity extends ModelException
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var array
     */
    private $ids;

    /**
     * @param string $resourceName
     * @param array  $ids
     */
    public function __construct($resourceName, array $ids)
    {
        $this->resourceName = $resourceName;
        $this->ids = $ids;

        parent::__construct(sprintf('Missing entities of %s with ids %s', $this->resourceName, implode(',', $ids)));
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'entity_missing';
    }
}
