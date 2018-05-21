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
use Trivago\Jade\Domain\ResourceManager\Bag\ToManyRelationship;
use Trivago\Jade\Domain\ResourceManager\Bag\ToOneRelationship;
use Trivago\Jade\Domain\ResourceManager\Exception\InvalidModelSet;
use Trivago\Jade\Domain\ResourceManager\Exception\MissingEntity;
use Trivago\Jade\Domain\ResourceManager\FieldResolver;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepository;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepositoryProvider;
use Trivago\Jade\Tests\BaseTest;

class FieldResolverTest extends BaseTest
{
    const RELATIONSHIP_CLASS = 'RelClass';

    /**
     * @var ResourceRepositoryProvider
     */
    protected $resourceRepositoryProviderProphecy;

    /**
     * @var ResourceRepository
     */
    protected $resourceRepositoryProphecy;

    /**
     * @return array
     */
    public function provider()
    {
        return [
            //Attributes Valid
            ['methodNoParameters', [], [], [], [], []],
            ['methodNoParameters', [], [], ['attr1' => 'value1'], ['attr1'], []],
            ['methodNoParameters', [], [], ['attr1' => 'value1', 'attr2' => 'value2'], ['attr1', 'attr2'], []],
            ['methodOneParameter', [], [], ['a' => 'aVal'], [], ['aVal']],
            ['methodOneParameter', [], [], ['a' => 'aVal', 'b' => 'bVal'], ['b'], ['aVal']],
            ['methodTwoParameters', [], [], ['a' => 'aVal', 'b' => 'bVal'], [], ['aVal', 'bVal']],
            ['methodTwoParameters', [], [], ['a' => 'aVal', 'b' => null], [], ['aVal', null]],
            //Relationships Valid
            ['methodNoParameters', ['rel1' => 1], ['rel1'], [], [], []],
            ['methodNoParameters', ['rel1' => [1]], ['rel1'], [], [], []],
            ['methodOneParameter', ['a' => 1], [], [], [], ['Object1']],
            ['methodOneParameter', ['a' => [1, 2], 'b' => 1], ['b'], [], [], [['Object1', 'Object2']]],
            ['methodTwoParameters', ['a' => [1, 2], 'b' => 1], [], [], [], [['Object1', 'Object2'], 'Object1']],
            //Mixed Valid
            ['methodNoParameters', ['rel1' => 1], ['rel1'], ['attr1' => 'val'], ['attr1'], []],
            ['methodOneParameter', ['rel1' => 1], ['rel1'], ['a' => 'val'], [], ['val']],
            ['methodOneParameter', ['a' => 1], [], ['a' => 'val'], ['a'], ['Object1']],
            ['methodTwoParameters', ['a' => 1], [], ['b' => 'val'], [], ['Object1', 'val']],
            ['methodTwoParameters', ['a' => 1, 'c' => [10, 20]], ['c'], ['b' => 'val'], [], ['Object1', 'val']],
            ['methodTwoParameters', ['a' => 1], [], ['b' => 'val', 'c' => [10, 20]], ['c'], ['Object1', 'val']],
            ['methodTwoParameters', ['a' => 1, 'd' => 2], ['d'], ['b' => 'val', 'c' => [10, 20]], ['c'], ['Object1', 'val']],
            ['methodTwoParameters', ['b' => 10], [], ['a' => 'value'], [], ['value', 'Object10']],
            //Missing parameter
            ['methodOneParameter', [], [], [], [], [], InvalidModelSet::class],
            ['methodOneParameter', [], [], ['b' => 1], [], [], InvalidModelSet::class],
            //Invalid method
            ['invalidMethod', [], [], [], [], [], \ReflectionException::class],
            //Missing entities
            ['methodOneParameter', ['a' => 1], [], [], [], [], MissingEntity::class, true],
            ['methodOneParameter', ['a' => [1, 2]], [], [], [], [], MissingEntity::class, true],
        ];
    }

    /**
     * @dataProvider provider
     *
     * @param string $method
     * @param array  $rawRelationships
     * @param array  $remainingRelationshipNames
     * @param array  $attributes
     * @param array  $remainingAttributeNames
     * @param array  $parameters
     * @param string $exceptionClass
     * @param bool   $missingEntities
     */
    public function testValidCases(
        $method,
        array $rawRelationships,
        array $remainingRelationshipNames,
        array $attributes,
        array $remainingAttributeNames,
        array $parameters,
        $exceptionClass = null,
        $missingEntities = false
    ) {
        if ($exceptionClass) {
            $this->expectException($exceptionClass);
        }
        $relationships = [];
        foreach ($rawRelationships as $relationshipName => $id) {
            if (is_array($id)) {
                $ids = $id;
                if ($missingEntities) {
                    $this->resourceRepositoryProphecy
                        ->fetchResourcesByIds($ids, [])
                        ->willReturn([]);
                } else {
                    $this->resourceRepositoryProphecy
                        ->fetchResourcesByIds($ids, [])
                        ->willReturn(array_map(function($objectId){return 'Object'.$objectId;}, $ids));
                }
                $relationships[$relationshipName] = new ToManyRelationship(
                    'target',
                    self::RELATIONSHIP_CLASS,
                    $id
                );
            } else {
                if ($missingEntities) {
                    $this->resourceRepositoryProphecy->fetchOneResource($id, [])->willReturn(null);
                } else {
                    $this->resourceRepositoryProphecy->fetchOneResource($id, [])->willReturn('Object'.$id);
                }

                $relationships[$relationshipName] = new ToOneRelationship(
                    'target',
                    self::RELATIONSHIP_CLASS,
                    $id
                );
            }
        }

        /** @var ResourceRepository $resourceRepository */
        $resourceRepository = $this->resourceRepositoryProphecy->reveal();

        $this->resourceRepositoryProviderProphecy
            ->getRepository(self::RELATIONSHIP_CLASS)
            ->willReturn($resourceRepository);
        /** @var ResourceRepositoryProvider $resourceRepositoryProvider */
        $resourceRepositoryProvider = $this->resourceRepositoryProviderProphecy->reveal();

        $fieldResolver = new FieldResolver($resourceRepositoryProvider);
        $resolverMethodParameters = $fieldResolver->resolveMethodParameters(
            ClassToTestFieldResolver::class,
            $method,
            new ResourceAttributeBag($attributes),
            new ResourceRelationshipBag($relationships)
        );

        self::assertEquals($remainingRelationshipNames, $resolverMethodParameters->getRemainingRelationshipNames());
        self::assertEquals($remainingAttributeNames, $resolverMethodParameters->getRemainingAttributeNames());
        self::assertEquals($parameters, $resolverMethodParameters->getParameters());
    }
}

class ClassToTestFieldResolver
{
    public function methodNoParameters()
    {

    }

    public function methodOneParameter($a)
    {

    }

    public function methodTwoParameters($a, $b)
    {

    }
}
