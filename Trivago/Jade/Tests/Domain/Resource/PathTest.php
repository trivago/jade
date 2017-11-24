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

namespace Trivago\Jade\Tests\Domain\Resource;

use Trivago\Jade\Domain\Resource\Exception\InvalidPath;
use Trivago\Jade\Domain\Resource\Path;
use Trivago\Jade\Tests\BaseTest;

class PathTest extends BaseTest
{
    public function validPathsProvider()
    {
        return [
            ['name', 'name', '', [], ['name']],
            ['owner.id', 'id', 'owner', ['owner'], ['owner', 'owner.id']],
            ['friends.country.cities.name', 'name', 'friends.country.cities', ['friends', 'country', 'cities'], [
                'friends',
                'friends.country',
                'friends.country.cities',
                'friends.country.cities.name'
            ]]
        ];
    }

    public function invalidPathsProvider()
    {
        return [
            ['.'],
            [''],
            ['.name'],
            ['name.'],
            ['owner..name'],
            ['owner.friend.name.'],
        ];
    }

    /**
     * @dataProvider validPathsProvider
     */
    public function testValidPaths($path, $columnName, $resourcePath, $relationshipChain, $allPossiblePaths)
    {
        $path = new Path($path);
        self::assertEquals($columnName, $path->getColumnName());
        self::assertEquals($resourcePath, $path->getResourcePath());
        self::assertArraySameElements($allPossiblePaths, $path->generateAllPossiblePaths());
        self::assertEquals($relationshipChain, $path->getRelationshipChain());
    }

    /**
     * @dataProvider invalidPathsProvider
     */
    public function testInvalidPaths($path)
    {
        $this->expectException(InvalidPath::class);

        new Path($path);
    }
}
