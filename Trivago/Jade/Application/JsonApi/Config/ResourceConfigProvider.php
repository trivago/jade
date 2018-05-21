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

class ResourceConfigProvider
{
    const INHERITABLE_FIELDS = [
        'relationships',
        'value_objects',
        'excluded_attributes',
        'virtual_paths',
        'attributes_permissions',
        'virtual_properties',
    ];

    /**
     * @var ResourceConfigValidator
     */
    private $configValidator;

    /**
     * @var ResourceConfig[]
     */
    private $resourceConfigs = [];

    /**
     * @param ResourceConfigValidator $configValidator
     * @param array                   $rawResourceConfigs
     * @param string                  $defaultReadRole
     * @param string                  $defaultManipulateRole
     * @param string                  $createMethod
     */
    public function __construct(
        ResourceConfigValidator $configValidator,
        array $rawResourceConfigs,
        $defaultReadRole,
        $defaultManipulateRole,
        $createMethod
    ) {
        $this->configValidator = $configValidator;
        $processedConfigs = [];
        foreach ($rawResourceConfigs as $rawResourceConfig) {
            $this->configValidator->validateEntityClass(
                $rawResourceConfig['name'],
                $rawResourceConfig['entity_class'],
                $rawResourceConfig['allowed_actions'],
                $createMethod
            );
            $this->setupDefaultValue($rawResourceConfig, $defaultReadRole, $defaultManipulateRole);
            $processedConfigs[$rawResourceConfig['name']] = $rawResourceConfig;
        }

        foreach ($processedConfigs as $key => $processedResourceConfig) {
            $processedConfigs[$key]['level'] = 0;
            if (isset($processedResourceConfig['parent'])) {
                $processedConfigs[$key]['level'] = $this->getHierarchyLevel($processedConfigs, $processedResourceConfig);
                $processedConfigs[$processedResourceConfig['parent']]['children'][] = $processedResourceConfig['name'];
            }
        }

        // Sorting is needed to make sure all the inheritable fields are passed to lower children
        uasort($processedConfigs, function ($a, $b) { return strcmp($a['level'], $b['level']); });

        foreach ($processedConfigs as $resourceName => &$processedResourceConfig) {
            unset($processedResourceConfig['level']);
            $this->extendParentConfig($processedResourceConfig, $processedConfigs);
            $this->configValidator->validate($resourceName, $processedResourceConfig, $processedConfigs);

            $resourceConfig = ResourceConfig::createWithData($processedResourceConfig);
            $this->resourceConfigs[$resourceConfig->getName()] = $resourceConfig;
        }
    }

    /**
     * @return ResourceConfig[]
     */
    public function getResourceConfigs()
    {
        return $this->resourceConfigs;
    }

    /**
     * @param string $name
     * @return ResourceConfig
     */
    public function getResourceConfig($name)
    {
        if (!isset($this->resourceConfigs[$name])) {
            throw new \InvalidArgumentException(
                sprintf('No resource config found for resource name %s.', $name)
            );
        }
        return $this->resourceConfigs[$name];
    }

    /**
     * @param array  $rawResourceConfig
     * @param string $defaultReadRole
     * @param string $defaultManipulateRole
     */
    private function setupDefaultValue(array &$rawResourceConfig, $defaultReadRole, $defaultManipulateRole)
    {
        if (!isset($rawResourceConfig['roles']['read'])) {
            $rawResourceConfig['roles']['read'] = $defaultReadRole;
        }
        foreach (['create', 'update', 'delete'] as $action) {
            if (!isset($rawResourceConfig['roles'][$action])) {
                $rawResourceConfig['roles'][$action] = $defaultManipulateRole;
            }
        }
    }

    /**
     * @param array $rawResourceConfig
     * @param array $processedConfigs
     */
    private function extendParentConfig(array &$rawResourceConfig, array $processedConfigs)
    {
        if (null === $rawResourceConfig['parent']) {
            return;
        }

        if (!array_key_exists($rawResourceConfig['parent'], $processedConfigs)) {
            throw new \InvalidArgumentException('There is no resource called '.$rawResourceConfig['parent']);
        }
        $parentRawConfig = $processedConfigs[$rawResourceConfig['parent']];

        foreach (self::INHERITABLE_FIELDS as $field) {
            $parentValue = $parentRawConfig[$field];
            $rawResourceConfig[$field] = array_merge($rawResourceConfig[$field], $parentValue);
        }
    }

    /**
     * @param array $allConfig
     * @param array $resourceConfig
     *
     * @return int
     */
    private function getHierarchyLevel(array $allConfig, array $resourceConfig)
    {
        $level = 0;
        while (isset($resourceConfig['parent'])) {
            $level++;
            $resourceConfig = $allConfig[$resourceConfig['parent']];
        }

        return $level;
    }
}

