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

namespace Trivago\Jade\Tests\Domain\ResourceManager;

use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ToOneRelationship;
use Trivago\Jade\Domain\ResourceManager\FieldResolver;
use Trivago\Jade\Domain\ResourceManager\GenericResourceManager;
use Trivago\Jade\Domain\ResourceManager\ResolvedMethodParameters;
use Trivago\Jade\Tests\BaseTest;

class GenericResourceManagerTest extends BaseTest
{
    /**
     * @var FieldResolver
     */
    protected $fieldResolverProphecy;

    public function testCreateValid()
    {
        $toOneRelationship = new ToOneRelationship('test', 'FakeClass', 1);
        $attributes = new ResourceAttributeBag(['optional1' => 'optional1']);
        $relationships = new ResourceRelationshipBag(['optional2' => $toOneRelationship]);
        $this->fieldResolverProphecy
            ->resolveMethodParameters(TestEntity::class, 'create', $attributes, $relationships)
            ->willReturn(new ResolvedMethodParameters(['m1', 'm2'], ['optional1'], ['optional2']));
        $this->fieldResolverProphecy
            ->getRelationshipValue($toOneRelationship)
            ->willReturn('optional2');
        /** @var FieldResolver $fieldResolver */
        $fieldResolver = $this->fieldResolverProphecy->reveal();

        $genericResourceManager = new GenericResourceManager($fieldResolver, 'create', 'updated');
        /** @var TestEntity $entity */
        $entity = $genericResourceManager->create($attributes, $relationships, TestEntity::class);
        self::assertInstanceOf(TestEntity::class, $entity);
        self::assertEquals('m1', $entity->getMandatory1());
        self::assertEquals('m2', $entity->getMandatory2());
        self::assertEquals('optional1', $entity->getOptional1());
        self::assertEquals('optional2', $entity->getOptional2());
        self::assertFalse($entity->isUpdatedCalled());
    }

    public function testCreateNotExisting()
    {
        $this->expectException(\LogicException::class);
        $attributes = new ResourceAttributeBag([]);
        $relationships = new ResourceRelationshipBag([]);
        $this->fieldResolverProphecy
            ->resolveMethodParameters(TestEntity::class, 'invalidMethod', $attributes, $relationships)
            ->willReturn(new ResolvedMethodParameters([], [], []));

        /** @var FieldResolver $fieldResolver */
        $fieldResolver = $this->fieldResolverProphecy->reveal();
        $genericResourceManager = new GenericResourceManager($fieldResolver, 'invalidMethod', 'updated');

        $genericResourceManager->create($attributes, $relationships, TestEntity::class);
    }

    public function testCreateInvalidReturnObject()
    {
        $this->expectException(\LogicException::class);
        $attributes = new ResourceAttributeBag([]);
        $relationships = new ResourceRelationshipBag([]);
        $this->fieldResolverProphecy
            ->resolveMethodParameters(TestEntity::class, 'createWrongObject', $attributes, $relationships)
            ->willReturn(new ResolvedMethodParameters([], [], []));

        /** @var FieldResolver $fieldResolver */
        $fieldResolver = $this->fieldResolverProphecy->reveal();
        $genericResourceManager = new GenericResourceManager($fieldResolver, 'createWrongObject', 'updated');

        $genericResourceManager->create($attributes, $relationships, TestEntity::class);
    }
}

class TestEntity
{
    private $mandatory1;
    private $mandatory2;
    private $optional1;
    private $optional2;
    private $private;
    private $isUpdatedCalled = false;

    public static function create($mandatory1, $mandatory2)
    {
        $test = new self();
        $test->mandatory1 = $mandatory1;
        $test->mandatory2 = $mandatory2;

        return $test;
    }

    public static function createWrongObject()
    {
        return null;
    }

    public function updated()
    {
        $this->isUpdatedCalled = true;
    }

    public function isUpdatedCalled()
    {
        return $this->isUpdatedCalled;
    }

    /**
     * @param mixed $optional1
     */
    public function setOptional1($optional1)
    {
        $this->optional1 = $optional1;
    }

    /**
     * @return mixed
     */
    public function getOptional2()
    {
        return $this->optional2;
    }

    /**
     * @param mixed $optional2
     */
    public function setOptional2($optional2)
    {
        $this->optional2 = $optional2;
    }

    /**
     * @return mixed
     */
    public function getMandatory1()
    {
        return $this->mandatory1;
    }

    /**
     * @return mixed
     */
    public function getMandatory2()
    {
        return $this->mandatory2;
    }

    /**
     * @return mixed
     */
    public function getOptional1()
    {
        return $this->optional1;
    }
}
