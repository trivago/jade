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

class ExpressionFilter extends Filter
{
    /**
     * @var Path
     */
    private $path;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param string $path
     * @param string $type
     * @param mixed  $value
     *
     * @throws InvalidPath
     */
    public function __construct($path, $type, $value)
    {
        self::validate($type, $value);
        $this->path = new Path($path);
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @param string $type
     * @param mixed  $value
     */
    public static function validate($type, $value)
    {
        if (!ExpressionFilterTypes::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Invalid type %s passed', $type));
        }
        if (!is_array($value) && in_array($type, [ExpressionFilterTypes::IN, ExpressionFilterTypes::NOT_IN], true)) {
            throw new \InvalidArgumentException('For in and nin the value should be an array');
        }
        if (null === $value && !in_array($type, [ExpressionFilterTypes::EQUAL_TO, ExpressionFilterTypes::NOT_EQUAL_TO], true)) {
            throw new \InvalidArgumentException('Null is not accepted for any operator other than eq and neq');
        }

        if (!is_numeric($value) && in_array($type[0], ['g', 'l'], true)) {
            throw new \InvalidArgumentException(sprintf('For gt, gte, lt, lte only numeric values are accepted'));
        }
    }

    /**
     * @return Path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
