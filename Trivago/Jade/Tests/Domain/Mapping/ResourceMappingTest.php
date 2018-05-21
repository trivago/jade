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

namespace Trivago\Jade\Tests\Domain\Mapping;

use Trivago\Jade\Domain\Mapping\Property\StaticProperty;
use Trivago\Jade\Domain\Mapping\ResourceMapping;
use Trivago\Jade\Tests\BaseTest;

class ResourceMappingTest extends BaseTest
{
    const TEST_PROPERTY_NAME = 'property';
    const TEST_METHOD_NAME = 'getProperty';

    public function testProperties()
    {
        $resourceMapping = new ResourceMapping();

        $property = new StaticProperty(self::TEST_PROPERTY_NAME, self::TEST_METHOD_NAME);
        self::assertCount(0, $resourceMapping->getProperties());
        $resourceMapping->addProperty($property);
        self::assertTrue($resourceMapping->hasProperty(self::TEST_PROPERTY_NAME));
        self::assertFalse($resourceMapping->hasProperty('NON_EXISTENCE_PROPERTY'));
        self::assertEquals($resourceMapping->getProperty(self::TEST_PROPERTY_NAME), $property);
        self::assertCount(1, $resourceMapping->getProperties());

        self::assertCount(0, $resourceMapping->getRelationships());
        $resourceMapping->addRelationship($property);
        self::assertTrue($resourceMapping->hasRelationship(self::TEST_PROPERTY_NAME));
        self::assertFalse($resourceMapping->hasRelationship('NON_EXISTENCE_RELATIONSHIP'));
        self::assertEquals($resourceMapping->getRelationship(self::TEST_PROPERTY_NAME), $property);
        self::assertCount(1, $resourceMapping->getRelationships());
    }
}
