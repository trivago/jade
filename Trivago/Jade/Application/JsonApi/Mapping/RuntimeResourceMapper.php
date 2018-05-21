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

namespace Trivago\Jade\Application\JsonApi\Mapping;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;
use Trivago\Jade\Application\Security\AttributePermission;
use Trivago\Jade\Application\Security\AttributePermissionByMethod;
use Trivago\Jade\Application\Security\AttributePermissionByRole;
use Trivago\Jade\Domain\Mapping\Property\StaticProperty;
use Trivago\Jade\Domain\Mapping\Property\VirtualProperty;
use Trivago\Jade\Domain\Mapping\ResourceMapper;
use Trivago\Jade\Domain\Mapping\ResourceMapping;

class RuntimeResourceMapper implements ResourceMapper
{
    /**
     * @var ResourceConfigProvider
     */
    private $resourceConfigProvider;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param ResourceConfigProvider        $resourceConfigProvider
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(
        ResourceConfigProvider $resourceConfigProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->resourceConfigProvider = $resourceConfigProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceMapping($resourceName, $resource)
    {
        $resourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceName);
        $reflection = new \ReflectionClass($resourceConfig->getEntityClass());
        $mapping = new ResourceMapping();

        $this->setupAttributes($reflection, $resourceConfig, $resource, $mapping);
        $this->setupRelationships($reflection, $resourceConfig, $mapping);

        return $mapping;
    }

    /**
     * @param \ReflectionClass $reflection
     * @param ResourceConfig   $resourceConfig
     * @param object           $resource
     * @param ResourceMapping  $mapping
     */
    private function setupAttributes(\ReflectionClass $reflection, ResourceConfig $resourceConfig, $resource, ResourceMapping $mapping)
    {
        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            if (
                'id' === $propertyName
                || $resourceConfig->hasRelationship($propertyName)
                || $resourceConfig->isAttributeExcluded($propertyName)
            ) {
                continue;
            }

            if ($resourceConfig->hasAttributePermissionFor($propertyName) && !$this->hasPermission($resource, $resourceConfig->getAttributePermissionsFor($propertyName))) {
                    continue;
                }

            $ucPropertyName = ucfirst($propertyName);
            $methods = ['get'.$ucPropertyName, 'has'.$ucPropertyName, 'is'.$ucPropertyName, $propertyName];
            foreach ($methods as $method) {
                if ($reflection->hasMethod($method)) {
                    $mapping->addProperty(new StaticProperty($propertyName, $method));

                    break;
                }
            }
        }

        foreach ($resourceConfig->getVirtualProperties() as $virtualPropertyName => $virtualPropertyMethod) {
            $mapping->addProperty(new VirtualProperty($virtualPropertyName, $virtualPropertyMethod));
        }
    }

    /**
     * @param \ReflectionClass $reflection
     * @param ResourceConfig   $resourceConfig
     * @param ResourceMapping  $mapping
     */
    private function setupRelationships(\ReflectionClass $reflection, ResourceConfig $resourceConfig, ResourceMapping $mapping)
    {
        foreach ($resourceConfig->getRelationshipNames() as $relationshipName) {
            $methods = ['get'.ucfirst($relationshipName), $relationshipName];
            foreach ($methods as $method) {
                if ($reflection->hasMethod($method)) {
                    $mapping->addRelationship(new StaticProperty($relationshipName, $method));

                    break;
                }
            }
        }
    }

    /**
     * @param object                  $resource
     * @param AttributePermission[][] $permissions
     *
     * @return bool
     */
    private function hasPermission($resource, $permissions)
    {
        if (!$this->tokenStorage->getToken()) {
            return false;
        }
        foreach ($permissions as $andPermissions) {
            foreach ($andPermissions as $permission) {
                if ($permission instanceof AttributePermissionByRole) {
                    if (!$this->authorizationChecker->isGranted($permission->getRole())) {
                        continue 2;
                    }
                } elseif ($permission instanceof AttributePermissionByMethod) {
                    $method = $permission->getMethod();
                    $user = $this->tokenStorage->getToken()->getUser();
                    if (!is_object($user)) {
                        continue 2;
                    }
                    if (!$resource->$method($user)) {
                        continue 2;
                    }
                } else {
                    throw new \LogicException(sprintf('Class %s is not supported for permission check', get_class($permissions)));
                }
            }

            return true;
        }

        return false;
    }
}
