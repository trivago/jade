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

class ExpressionFilterTypes
{
    const ALL = [
        self::EQUAL_TO,
        self::NOT_EQUAL_TO,
        self::GREATER_THAN,
        self::GREATER_THAN_EQUAL,
        self::LESS_THAN,
        self::LESS_THAN_EQUAL,
        self::CONTAINS,
        self::NOT_CONTAINS,
        self::IN,
        self::NOT_IN,
    ];

    const EQUAL_TO = 'eq';
    const NOT_EQUAL_TO = 'neq';
    const GREATER_THAN = 'gt';
    const GREATER_THAN_EQUAL = 'gte';
    const LESS_THAN = 'lt';
    const LESS_THAN_EQUAL = 'lte';
    const CONTAINS = 'c';
    const NOT_CONTAINS = 'nc';
    const IN = 'in';
    const NOT_IN = 'nin';

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid($type)
    {
        return in_array($type, self::ALL, true);
    }
}
