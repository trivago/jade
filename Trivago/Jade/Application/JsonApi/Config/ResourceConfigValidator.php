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

namespace Trivago\Jade\Application\JsonApi\Config;

class ResourceConfigValidator
{
    /**
     * @param string $resourceName
     * @param array  $processedResourceConfig
     * @param array  $processedConfigs
     */
    public function validate($resourceName, array $processedResourceConfig, array $processedConfigs)
    {
        $this->validateRelationships(
            $resourceName,
            $processedResourceConfig['relationships'],
            $processedConfigs
        );
        $this->validateValueObjects(
            $resourceName,
            $processedResourceConfig['value_objects']
        );
        $this->validateVirtualProperties(
            $resourceName,
            $processedResourceConfig['entity_class'],
            $processedResourceConfig['virtual_properties']
        );
    }

    /**
     * @param string $resourceName
     * @param string $entityClass
     * @param array  $allowedActions
     * @param string $createMethod
     */
    public function validateEntityClass($resourceName, $entityClass, array $allowedActions, $createMethod)
    {
        if (!class_exists($entityClass)) {
            throw new \InvalidArgumentException(
                sprintf('The class provided for "%s" does not exist.', $resourceName)
            );
        }
        $reflectionClass = new \ReflectionClass($entityClass);
        if (in_array(ResourceConfig::ACTION_CREATE, $allowedActions, true) && !$reflectionClass->hasMethod($createMethod)) {
            throw new \InvalidArgumentException(sprintf('Missing static method %s on %s', $createMethod, $entityClass));
        }
    }

    /**
     * @param string $resourceName
     * @param array  $relationships
     * @param array  $processedConfigs
     */
    public function validateRelationships($resourceName, array $relationships, array $processedConfigs)
    {
        foreach ($relationships as $relationship) {
            if (!array_key_exists($relationship['type'], $processedConfigs)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid type "%s" provided for %s.%s', $relationship['type'], $resourceName, $relationship['name'])
                );
            }
        }
    }

    /**
     * @param string $resourceName
     * @param array  $valueObjects
     */
    public function validateValueObjects($resourceName, array $valueObjects)
    {
        foreach ($valueObjects as $attributeName => $valueObjectClass) {
            if (!class_exists($valueObjectClass)) {
                throw new \InvalidArgumentException(
                    sprintf('Class "%s" does not exist for value object of %s.%s', $valueObjectClass, $resourceName, $attributeName)
                );
            }

            $reflectionClass = new \ReflectionClass($valueObjectClass);
            if (!$reflectionClass->isInstantiable()) {
                throw new \InvalidArgumentException(
                    sprintf('Class "%s" is not instantiable for value object of %s.%s', $valueObjectClass, $resourceName, $attributeName)
                );
            }
        }
    }

    /**
     * @param string $resourceName
     * @param string $entityClass
     * @param array  $virtualProperty
     */
    public function validateVirtualProperties($resourceName, $entityClass, array $virtualProperty)
    {
        foreach ($virtualProperty as $propertyName => $method) {
            if (!method_exists($entityClass, $method)) {
            throw new \LogicException(
                sprintf(
                    'Method "%s" does not exists on class "%s" defined on virtual property of "%s"',
                    $method,
                    $entityClass,
                    $resourceName
                )
            );
        }
        }
    }
}
