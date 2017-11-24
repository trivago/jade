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

namespace Trivago\Jade\Domain\ResourceManager;

use Trivago\Jade\Domain\ResourceManager\Bag\Relationship;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;
use Trivago\Jade\Domain\ResourceManager\Exception\InvalidModelSet;

class GenericResourceManager implements ResourceManager
{
    /**
     * @var FieldResolver
     */
    private $fieldResolver;

    /**
     * @var string
     */
    private $createMethod;

    /**
     * @var string
     */
    private $onUpdateMethod;

    /**
     * @param FieldResolver $fieldResolver
     * @param string $createMethod
     * @param string $onUpdateMethod
     */
    public function __construct(FieldResolver $fieldResolver, $createMethod, $onUpdateMethod)
    {
        $this->fieldResolver = $fieldResolver;
        $this->createMethod = $createMethod;
        $this->onUpdateMethod = $onUpdateMethod;
    }

    /**
     * @inheritdoc
     */
    public function create(ResourceAttributeBag $resourceAttributeBag, ResourceRelationshipBag $resourceRelationshipBag, $entityClass)
    {
        $resolvedMethodParameters = $this->fieldResolver->resolveMethodParameters(
            $entityClass,
            $this->createMethod,
            $resourceAttributeBag,
            $resourceRelationshipBag
        );
        $createParameters = $resolvedMethodParameters->getParameters();
        $remainingAttributeNames = $resolvedMethodParameters->getRemainingAttributeNames();
        $remainingRelationshipNames = $resolvedMethodParameters->getRemainingRelationshipNames();

        if (!method_exists($entityClass, $this->createMethod)) {
            throw new \LogicException(sprintf(
                'The class "%s" should implement static method "%s"',
                $entityClass,
                $this->createMethod
            ));
        }
        $entity = call_user_func_array([$entityClass, $this->createMethod], $createParameters);
        if (!is_object($entity) || get_class($entity) !== $entityClass) {
            throw new \LogicException('The returned object by create method should be an object of class '.$entityClass);
        }

        $remainingAttributes = [];
        foreach ($remainingAttributeNames as $attributeName) {
            $remainingAttributes[$attributeName] = $resourceAttributeBag->getValue($attributeName);
        }

        $remainingRelationships = [];
        foreach ($remainingRelationshipNames as $relationshipName) {
            $remainingRelationships[$relationshipName] = $resourceRelationshipBag->getValue($relationshipName);
        }

        $this->updateAttributes($entity, new ResourceAttributeBag($remainingAttributes));
        $this->updateRelationships($entity, new ResourceRelationshipBag($remainingRelationships));

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function update($entity, ResourceAttributeBag $resourceAttributeBag, ResourceRelationshipBag $resourceRelationshipBag)
    {
        $this->updateAttributes($entity, $resourceAttributeBag);
        $this->updateRelationships(
            $entity,
            $resourceRelationshipBag
        );

        $onUpdateMethod = $this->onUpdateMethod;
        if (method_exists($entity, $onUpdateMethod)) {
            $entity->$onUpdateMethod();
        }
    }

    /**
     * @inheritdoc
     */
    public function updateAttributes($entity, ResourceAttributeBag $resourceAttributeBag)
    {
        foreach ($resourceAttributeBag->getAllAttributeNames() as $attributeName) {
            $method = 'set'.ucfirst($attributeName);
            if (!method_exists($entity, $method)) {
                throw new InvalidModelSet(
                    'attributes.'.$attributeName,
                    sprintf('Property "%s" can not be set as it has no setter.', $attributeName)
                );
            }
            $entity->$method($resourceAttributeBag->getValue($attributeName));
        }
    }

    /**
     * @inheritdoc
     */
    public function updateRelationships($entity, ResourceRelationshipBag $resourceRelationshipBag)
    {
        foreach ($resourceRelationshipBag->getAllRelationshipNames() as $relationshipName) {
            $this->updateRelationship(
                $relationshipName,
                $resourceRelationshipBag->getValue($relationshipName),
                $entity
            );
        }
    }

    /**
     * @param string $relationshipName
     * @param Relationship $relationship
     * @param object $entity
     */
    private function updateRelationship($relationshipName, Relationship $relationship, $entity)
    {
        $method = 'set'.ucfirst($relationshipName);
        if (!method_exists($entity, $method)) {
            throw new InvalidModelSet(
                'relationships.'.$relationshipName,
                sprintf('Relationship "%s" can not be set as it has no setter.', $relationshipName)
            );
        }

        $entity->$method($this->fieldResolver->getRelationshipValue($relationship));
    }


}
