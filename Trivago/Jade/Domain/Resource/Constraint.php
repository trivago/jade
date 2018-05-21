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

class Constraint
{
    const DEFAULT_PAGE_NUMBER = 1;
    const DEFAULT_PER_PAGE = 50;

    /**
     * @var int
     */
    private $pageNumber = self::DEFAULT_PAGE_NUMBER;

    /**
     * @var int
     */
    private $perPage = self::DEFAULT_PER_PAGE;

    /**
     * @var CompositeFilter
     */
    private $filterCollection;

    public function __construct()
    {
        $this->filterCollection = new CompositeFilter(CompositeFilterTypes::AND_EXPRESSION, []);
    }

    /**
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * @param int $pageNumber
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = (int) $pageNumber;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     */
    public function setPerPage($perPage)
    {
        $this->perPage = (int) $perPage;
    }

    /**
     * @return CompositeFilter
     */
    public function getFilterCollection()
    {
        return $this->filterCollection;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filterCollection->getFilters();
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filterCollection->addFilter($filter);
    }
}
