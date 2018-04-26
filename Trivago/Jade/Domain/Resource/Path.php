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

use Trivago\Jade\Domain\Resource\Exception\InvalidPath;

class Path
{
    /**
     * @var string
     */
    private $fullPath;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $resourcePath;

    /**
     * @param string $path
     *
     * @throws InvalidPath
     */
    public function __construct($path)
    {
        $path = trim($path);
        if (!$path) {
            throw new InvalidPath($path, 'The path can not be empty.');
        }
        $this->fullPath = $path;
        $pathParts = explode('.', $path);
        if (count($pathParts) !== count(array_filter($pathParts))) {
            throw new InvalidPath($path, 'The path can not contain empty parts');
        }
        $this->columnName = array_pop($pathParts);
        if (!count($pathParts)) {
            return;
        }
        $this->resourcePath = implode('.', $pathParts);
    }

    /**
     * @return string
     */
    public function getResourcePath()
    {
        return $this->resourcePath;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * @return array
     */
    public function getRelationshipChain()
    {
        return array_filter(explode('.', $this->getResourcePath()));
    }

    /**
     * @return array
     */
    public function generateAllPossiblePaths()
    {
        $chain = [];
        $possiblePaths = [];
        foreach (explode('.', $this->getFullPath()) as $pathPart) {
            $chain[] = $pathPart;
            $possiblePaths[] = implode('.', $chain);
        }

        return $possiblePaths;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->fullPath;
    }
}
