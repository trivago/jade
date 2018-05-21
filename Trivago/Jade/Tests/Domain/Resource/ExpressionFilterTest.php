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

namespace Trivago\Jade\Tests\Domain\Resource;

use Trivago\Jade\Domain\Resource\ExpressionFilter;
use Trivago\Jade\Tests\BaseTest;

class ExpressionFilterTest extends BaseTest
{
    /**
     * @return array
     */
    public function invalidExpressions()
    {
        return [
            ['invalid_type', 'not important value'],
            ['', 'not important value'],
            [null, 'not important value'],
            [false, 'not important value'],
            [true, 'not important value'],
            ['in', 'not important value'],
            ['in', 'not important value'],
            ['gt', null],
            ['gte', null],
            ['lt', null],
            ['lte', null],
            ['gt', []],
            ['gte', []],
            ['lt', []],
            ['lte', []],
            ['gt', 'string'],
            ['gte', 'string'],
            ['lt', 'string'],
            ['lte', 'string'],
            ['in', null],
            ['nin', null],
            ['c', null],
        ];
    }

    /**
     * @return array
     */
    public function validExpressions()
    {
        return [
            ['gt', 1],
            ['gte', 2],
            ['lt', 3],
            ['lte', 0.3],
            ['in', [123]],
            ['nin', [1,2]],
            ['c', 1.1],
            ['c', 'string'],
        ];
    }

    /**
     * @dataProvider invalidExpressions
     *
     * @expectedException \InvalidArgumentException
     *
     * @param string $type
     * @param string $value
     */
    public function testInvalidExpressionFilter($type, $value)
    {
        new ExpressionFilter('path', $type, $value);
    }

    /**
     * @dataProvider validExpressions
     *
     * @param string $type
     * @param string $value
     */
    public function testValidExpressionFilter($type, $value)
    {
        $filter = new ExpressionFilter('path', $type, $value);
        $this->assertEquals('path', $filter->getPath());
        $this->assertEquals($type, $filter->getType());
        $this->assertEquals($value, $filter->getValue());
    }
}
