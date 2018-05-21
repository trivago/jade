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

namespace Trivago\Jade\Domain\Resource;

class CompositeFilter extends Filter
{
    /**
     * @var Filter[]
     */
    private $filters = [];

    /**
     * @param string   $type
     * @param Filter[] $expressions
     */
    public function __construct($type, array $expressions)
    {
        if (!CompositeFilterTypes::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Invalid type %s passed', $type));
        }
        $this->type = $type;
        foreach ($expressions as $expression) {
            $this->addFilter($expression);
        }
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param int    $key
     * @param Filter $filter
     */
    public function replaceFilter($key, Filter $filter)
    {
        $this->filters[$key] = $filter;
    }

    /**
     * @return Path[]
     */
    public function getAllPaths()
    {
        $paths = [];
        foreach ($this->getFilters() as $filter) {
            if ($filter instanceof ExpressionFilter) {
                $paths[] = $filter->getPath();
            } elseif ($filter instanceof self) {
                $paths = array_merge($paths, $filter->getAllPaths());
            } else {
                throw new \LogicException('Expected ExpressionFilter or CompositeFilter. Received '.get_class($filter));
            }
        }

        return array_unique($paths);
    }
}
