<?php

/*
 * Copyright (c) 2017 trivago
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
 *
 * @author Moein Akbarof <moein.akbarof@trivago.com>
 * @date 2017-09-10
 */

namespace Trivago\Jade\Domain\Resource;

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
     * @param mixed $value
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
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public static function validate($type, $value)
    {
        if (!ExpressionFilterTypes::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('Invalid type %s passed', $type));
        }
        if (in_array($type, [ExpressionFilterTypes::IN, ExpressionFilterTypes::NOT_IN]) && !is_array($value)) {
            throw new \InvalidArgumentException('For in and nin the value should be an array');
        }
        if (!in_array($type, [ExpressionFilterTypes::EQUAL_TO, ExpressionFilterTypes::NOT_EQUAL_TO]) && null === $value) {
            throw new \InvalidArgumentException('Null is not accepted for any operator other than eq and neq');
        }

        if (in_array($type[0], ['g', 'l']) && !is_numeric($value)) {
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
