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

namespace Trivago\Jade\Application\JsonApi\Request;

use Trivago\Jade\Domain\Resource\Constraint;
use Trivago\Jade\Domain\Resource\SortCollection;

class CollectionRequest extends ResourceRequest
{
    /**
     * @var string[]
     */
    protected $fieldsets = [];

    /**
     * @var string[]
     */
    protected $relationships = [];

    /**
     * @var Constraint
     */
    protected $constraint;

    /**
     * @var SortCollection
     */
    protected $sorts;

    /**
     * @param string         $resourceName
     * @param string[]       $fieldsets
     * @param string[]       $relationships
     * @param Constraint     $constraint
     * @param SortCollection $sorts
     */
    public function __construct(
        $resourceName,
        array $fieldsets,
        array $relationships,
        Constraint $constraint,
        SortCollection $sorts
    ) {
        parent::__construct($resourceName);
        $this->fieldsets = $fieldsets;
        $this->relationships = $relationships;
        $this->constraint = $constraint;
        $this->sorts = $sorts;
    }

    /**
     * @return string[]
     */
    public function getFieldsets()
    {
        return $this->fieldsets;
    }

    /**
     * @return string[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @return Constraint
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * @return SortCollection
     */
    public function getSorts()
    {
        return $this->sorts;
    }
}
