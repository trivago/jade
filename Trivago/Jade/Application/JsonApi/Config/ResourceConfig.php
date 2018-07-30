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

use Trivago\Jade\Application\JsonApi\Mapping\DynamicSchema;
use Neomerx\JsonApi\Schema\SchemaProvider;
use Trivago\Jade\Application\Security\AttributePermission;
use Trivago\Jade\Application\Security\AttributePermissionByMethod;
use Trivago\Jade\Application\Security\AttributePermissionByRole;

class ResourceConfig
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_READ   = 'read';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string[]
     */
    private $entityClassAliases = [];

    /**
     * @var SchemaProvider
     */
    private $schemaProvider = DynamicSchema::class;

    /**
     * @var ResourceRelationship[]
     */
    private $relationships = [];

    /**
     * @var string
     */
    private $repositoryServiceId;

    /**
     * @var string
     */
    private $managerServiceId;

    /**
     * @var array
     */
    private $excludedAttributes = [];

    /**
     * @var AttributePermission[][][]
     */
    private $attributesPermissions = [];

    /**
     * @var array
     */
    private $valueObjects = [];

    /**
     * @var array
     */
    private $virtualPaths = [];

    /**
     * @var array
     */
    private $virtualProperties = [];

    /**
     * @var array
     */
    private $allowedActions = [];

    /**
     * @var string
     */
    private $parent;

    /**
     * @var array
     */
    private $roles;

    /**
     * @var array
     */
    private $children = [];

    /**
     * @param array $data
     *
     * @return ResourceConfig
     */
    public static function createWithData($data)
    {
        $resourceConfig = new self();

        foreach ($data as $key => $value) {
            if ($key === 'relationships') {
                $relationshipNames = [];
                foreach ($value as $relationship) {
                    $relationshipName = $relationship['name'];
                    if (in_array($relationshipName, $relationshipNames, true)) {
                        throw new \InvalidArgumentException(
                            sprintf('Duplicated relationship "%s" found. Maybe because of the parent?', $relationshipName)
                        );
                    }
                    $relationshipNames[] = $relationshipName;
                    $resourceConfig->relationships[] = new ResourceRelationship($relationshipName, $relationship['type']);
                }
                continue;
            } elseif ('attributes_permissions' === $key) {
                foreach ($value as $attributeName => $orPermissions) {
                    foreach ($orPermissions as $andPermissions) {
                        $objectAndPermissions = [];
                        foreach ($andPermissions as $permission) {
                            switch ($permission[0]) {
                                case 'byRole':
                                    $objectAndPermissions[] = new AttributePermissionByRole($attributeName, $permission[1]);
                                    break;
                                case 'byMethod':
                                    $objectAndPermissions[] = new AttributePermissionByMethod($attributeName, $permission[1]);
                                    break;
                                default:
                                    throw new \InvalidArgumentException('Invalid permission type '.print_r($permission[0], true));
                            }
                        }
                        $resourceConfig->attributesPermissions[$attributeName][] = $objectAndPermissions;
                    }
                }
                continue;
            }

            $camelCasedKey = self::underscoreToCamelCase($key);
            if (!property_exists(self::class, $camelCasedKey)) {
                throw new \InvalidArgumentException('There is no property with name '.$camelCasedKey);
            }
            $resourceConfig->$camelCasedKey = $value;
        }

        return $resourceConfig;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return mixed
     */
    public function getSchemaProvider()
    {
        return $this->schemaProvider;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getRealPath($path)
    {
        if (array_key_exists($path, $this->virtualPaths)) {
            return $this->virtualPaths[$path];
        }

        return $path;
    }

    /**
     * @return array
     */
    public function getVirtualProperties()
    {
        return $this->virtualProperties;
    }

    /**
     * @return ResourceRelationship[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @param string $attributeName
     *
     * @return bool
     */
    public function isAttributeExcluded($attributeName)
    {
        return in_array($attributeName, $this->excludedAttributes, true);
    }

    /**
     * @param string $attributeName
     *
     * @return AttributePermission[][]
     */
    public function getAttributePermissionsFor($attributeName)
    {
        if (!$this->hasAttributePermissionFor($attributeName)) {
            throw new \InvalidArgumentException('No permissions for '.$attributeName);
        }
        return $this->attributesPermissions[$attributeName];
    }

    /**
     * @param string $attributeName
     *
     * @return bool
     */
    public function hasAttributePermissionFor($attributeName)
    {
        return array_key_exists($attributeName, $this->attributesPermissions);
    }

    /**
     * @return string[]
     */
    public function getEntityClassAliases()
    {
        return $this->entityClassAliases;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasRelationship($name)
    {
        foreach ($this->getRelationships() as $relationship) {
            if ($relationship->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return ResourceRelationship
     */
    public function getRelationship($name)
    {
        foreach ($this->getRelationships() as $relationship) {
            if ($relationship->getName() === $name) {
                return $relationship;
            }
        }

        throw new \LogicException(sprintf('No relationship with name %s found', $name));
    }

    /**
     * @return string[]
     */
    public function getRelationshipNames()
    {
        return array_map(
            function (ResourceRelationship $relationship) {
                return $relationship->getName();
            },
            $this->relationships
        );
    }

    /**
     * @return string
     */
    public function getRepositoryServiceId()
    {
        return $this->repositoryServiceId;
    }

    /**
     * @return string
     */
    public function getManagerServiceId()
    {
        return $this->managerServiceId;
    }

    /**
     * @param string $attributeName
     *
     * @return array
     */
    public function getValueObjectFor($attributeName)
    {
        if (!$this->hasValueObject($attributeName)) {
            throw new \InvalidArgumentException('Not value object class for attribute '.$attributeName);
        }

        return $this->valueObjects[$attributeName];
    }

    /**
     * @param string $attributeName
     *
     * @return bool
     */
    public function hasValueObject($attributeName)
    {
        return array_key_exists($attributeName, $this->valueObjects);
    }

    /**
     * @return bool
     */
    public function isUpdateAllowed()
    {
        return $this->isAllowed(self::ACTION_UPDATE);
    }

    /**
     * @return bool
     */
    public function isCreateAllowed()
    {
        return $this->isAllowed(self::ACTION_CREATE);
    }

    /**
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return $this->isAllowed(self::ACTION_DELETE);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return (bool) count($this->getChildren());
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isTypeValid($type)
    {
        return in_array($type, $this->getValidTypes(), true);
    }

    /**
     * @return array
     */
    public function getValidTypes()
    {
        return array_merge([$this->getName()], $this->getChildren());
    }

    /**
     * @return string
     */
    public function getCreateRole()
    {
        return $this->getRoleFor(self::ACTION_CREATE);
    }

    /**
     * @return string
     */
    public function getUpdateRole()
    {
        return $this->getRoleFor(self::ACTION_UPDATE);
    }

    /**
     * @return string
     */
    public function getDeleteRole()
    {
        return $this->getRoleFor(self::ACTION_DELETE);
    }

    /**
     * @return string
     */
    public function getReadRole()
    {
        return $this->getRoleFor(self::ACTION_READ);
    }

    /**
     * @param string $action
     *
     * @return string
     */
    private function getRoleFor($action)
    {
        return $this->roles[$action];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function underscoreToCamelCase($string)
    {
        $string = ucwords($string, '_');
        $string[0] = strtolower($string[0]);

        return str_replace('_', '', $string);
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    private function isAllowed($action)
    {
        return in_array($action, $this->allowedActions, true);
    }
}
