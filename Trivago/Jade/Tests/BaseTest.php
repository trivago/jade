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

namespace Trivago\Jade\Tests;

use Faker\Generator;
use PhpDocReader\PhpDocReader;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    const MOCK_SUFFIX = 'Prophecy';

    /** @var Generator */
    protected $faker;

    protected function tearDown()
    {
        $this->verifyMockObjects();
    }

    /**
     * Here every property ending with prophecy will be magically initialized with the corresponding prophecy!(El mago)
     */
    public function setUp()
    {
        $this->faker = \Faker\Factory::create();

        $reader = new PhpDocReader();

        foreach (array_keys(get_object_vars($this)) as $propertyName) {
            if (self::MOCK_SUFFIX !== substr($propertyName, -strlen(self::MOCK_SUFFIX))) {
                continue;
            }

            $property = new \ReflectionProperty(static::class, $propertyName);
            $propertyClass = $reader->getPropertyClass($property);

            $this->$propertyName = $this->prophesize($propertyClass);
        }
    }

    public static function assertObjectSameFields($object, $values)
    {
        foreach ($values as $property => $value) {
            static::assertEquals($value, $object->$property);
        }
    }

    public static function assertArraySameElements($array1, $array2)
    {
        static::assertTrue(array_diff($array1, $array2) === array_diff($array2, $array1));
    }
}

