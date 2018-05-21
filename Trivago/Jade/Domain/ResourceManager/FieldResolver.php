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

namespace Trivago\Jade\Domain\ResourceManager;

use Trivago\Jade\Domain\ResourceManager\Bag\NullRelationship;
use Trivago\Jade\Domain\ResourceManager\Bag\Relationship;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ToManyRelationship;
use Trivago\Jade\Domain\ResourceManager\Bag\ToOneRelationship;
use Trivago\Jade\Domain\ResourceManager\Exception\InvalidModelSet;
use Trivago\Jade\Domain\ResourceManager\Exception\MissingEntity;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepositoryProvider;

class FieldResolver
{
    /**
     * @var ResourceRepositoryProvider
     */
    private $repositoryProvider;

    /**
     * @param ResourceRepositoryProvider $repositoryProvider
     */
    public function __construct(ResourceRepositoryProvider $repositoryProvider)
    {
        $this->repositoryProvider = $repositoryProvider;
    }

    /**
     * @param string                  $class
     * @param string                  $method
     * @param ResourceAttributeBag    $resourceAttributeBag
     * @param ResourceRelationshipBag $resourceRelationshipBag
     *
     * @return ResolvedMethodParameters
     *
     * @throws MissingEntity
     * @throws InvalidModelSet
     */
    public function resolveMethodParameters(
        $class,
        $method,
        ResourceAttributeBag $resourceAttributeBag,
        ResourceRelationshipBag $resourceRelationshipBag
    ) {
        $methodParameters = [];
        $relationshipNames = $resourceRelationshipBag->getAllRelationshipNames();
        $attributeNames = $resourceAttributeBag->getAllAttributeNames();
        $constructorReflection = new \ReflectionMethod($class, $method);
        foreach ($constructorReflection->getParameters() as $reflectionParameter) {
            if ($resourceRelationshipBag->hasRelationship($reflectionParameter->getName())) {
                $methodParameters[] = $this->resolveRelationship($reflectionParameter, $relationshipNames, $resourceRelationshipBag);
            } else {
                $methodParameters[] = $this->resolveAttribute($reflectionParameter, $attributeNames, $resourceAttributeBag);
            }
        }

        return new ResolvedMethodParameters($methodParameters, array_values($attributeNames), array_values($relationshipNames));
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     * @param array                $attributeNames
     * @param ResourceAttributeBag $resourceAttributeBag
     *
     * @return mixed
     *
     * @throws InvalidModelSet
     */
    private function resolveAttribute(\ReflectionParameter $reflectionParameter, array &$attributeNames, ResourceAttributeBag $resourceAttributeBag)
    {
        $this->checkParameterExists($reflectionParameter, $attributeNames);

        return $resourceAttributeBag->getValue($reflectionParameter->getName());
    }

    /**
     * @param \ReflectionParameter    $reflectionParameter
     * @param array                   $relationshipNames
     * @param ResourceRelationshipBag $resourceRelationshipBag
     *
     * @return array|object
     *
     * @throws MissingEntity
     * @throws InvalidModelSet
     */
    private function resolveRelationship(\ReflectionParameter $reflectionParameter, array &$relationshipNames, ResourceRelationshipBag $resourceRelationshipBag)
    {
        $this->checkParameterExists($reflectionParameter, $relationshipNames);

        return $this->getRelationshipValue($resourceRelationshipBag->getValue($reflectionParameter->getName()));
    }

    /**
     * @param Relationship $relationship
     *
     * @return array|object
     *
     * @throws MissingEntity
     */
    public function getRelationshipValue(Relationship $relationship)
    {
        if ($relationship instanceof NullRelationship) {
            return null;
        }
        $repository = $this->repositoryProvider->getRepository($relationship->getEntityClass());
        if ($relationship instanceof ToOneRelationship) {
            $entity = $repository->fetchOneResource($relationship->getEntityId(), []);
            if (!$entity) {
                throw new MissingEntity($relationship->getTargetResourceName(), [$relationship->getEntityId()]);
            }

            return $entity;
        } elseif ($relationship instanceof ToManyRelationship) {
            $entities = $repository->fetchResourcesByIds($relationship->getEntityIds(), []);
            if (count($entities) !== count($relationship->getEntityIds())) {
                throw new MissingEntity($relationship->getTargetResourceName(), $relationship->getEntityIds());
            }

            return $entities;
        } else {
            throw new \LogicException(sprintf('Invalid class %s was passed for a relationship.', get_class($relationship)));
        }
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     * @param array                $passedValues
     *
     * @throws InvalidModelSet
     */
    private function checkParameterExists(\ReflectionParameter $reflectionParameter, array &$passedValues)
    {
        $attributePosition = array_search($reflectionParameter->getName(), $passedValues, true);
        if (false === $attributePosition) {
            throw new InvalidModelSet(
                '[attributes|relationships].'.$reflectionParameter->getName(),
                sprintf('Missing mandatory parameter "%s"', $reflectionParameter->getName())
            );
        }
        unset($passedValues[$attributePosition]);
    }
}
