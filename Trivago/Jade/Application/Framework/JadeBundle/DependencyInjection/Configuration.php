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

namespace Trivago\Jade\Application\Framework\JadeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('trivago_jade');
        $rootNode
            ->validate()
                ->ifTrue(function($values) {
                    $values['security']['enabled'] && (!$values['security']['default_manipulate_role'] || $values['security']['default_read_role']);
                })
                ->thenInvalid('When security is enabled you have to specify default_manipulate_role and default_read_role')
            ->end()
            ->validate()
                ->ifTrue(function($values) {
                    return $values['read']['max_per_page'] < $values['read']['default_per_page'];
                })
                ->thenInvalid('max_per_page can not be less than default_per_page.')
            ->end()
            ->children()
                ->scalarNode('debug')
                    ->info('If you set this true the exceptions will be thrown instead of being converted to error response.')
                    ->defaultFalse()
                    ->validate()
                        ->ifNotInArray([true, false])
                        ->thenInvalid('global.debug must be a boolean value.')
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('enabled')
                            ->info('If you do not use security in your project just set this value to false.')
                            ->defaultFalse()
                            ->validate()
                                ->ifNotInArray([true, false])
                                ->thenInvalid('security.enabled must be a boolean value.')
                            ->end()
                        ->end()
                        ->scalarNode('strict_filtering_and_sorting')
                            ->info('If you want to allow sorting and filtering using excluded attributes (columns) just set this value to false.')
                            ->defaultTrue()
                            ->validate()
                                ->ifNotInArray([true, false])
                                ->thenInvalid('security.strict_filtering_and_sorting must be a boolean value.')
                            ->end()
                        ->end()
                        ->scalarNode('default_manipulate_role')
                            ->info('Default role for creating/updating any resource. This can be changed on each resource.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('default_read_role')
                            ->info('Default role for reading any resource. This can be changed on each resource.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('read')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('max_per_page')
                            ->info('The maximum number of items per page')
                            ->defaultValue(100)
                            ->validate()
                                ->ifTrue(function($value) {
                                    return !is_int($value) || $value < 1;
                                })
                                ->thenInvalid('This field should be a numeric greater than 0.')
                            ->end()
                        ->end()
                        ->scalarNode('default_per_page')
                            ->info('The default number of items per page.')
                            ->defaultValue(50)
                            ->validate()
                                ->ifTrue(function($value) {
                                    return !is_int($value) || $value < 1;
                                })
                                ->thenInvalid('This field should be a numeric greater than 0.')
                            ->end()
                        ->end()
                        ->scalarNode('fetch_total_count')
                            ->info('If enabled the repository will also fetches the total count of the results and it will be returned in the api.')
                            ->defaultTrue()
                            ->validate()
                                ->ifNotInArray([true, false])
                                ->thenInvalid('This field can only be boolean')
                            ->end()
                        ->end()
                        ->scalarNode('max_relationship_depth')
                            ->info('Limits the consumer on how many level of include it can request. For example include=locationPreferences.country.cities will fail since it\'s 3 level of relationship')
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(function($value) {
                                    return !is_int($value) || $value < 0;
                                })
                                ->thenInvalid('The max depth should be a positive number')
                            ->end()
                            ->defaultValue(2)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('manipulate')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('include_relationships')
                            ->info(
                                'If set to true the relationships that are updated in the request will be returned in the response.'
                            )
                            ->defaultValue(false)
                            ->validate()
                                ->ifNotInArray([true, false])
                                ->thenInvalid('This value should be boolean.')
                            ->end()
                        ->end()
                        ->scalarNode('on_update_method')
                            ->info(
                                'The name of the function that is called on the resource when it\'s updated.'.PHP_EOL.
                                'The resource does not need to have this method. Mostly used to update updatedAt field or validate the object state.'
                            )
                            ->defaultValue('updated')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('create_method')
                            ->info('The name of the static method needed on each entity that allows creation of the entity.')
                            ->defaultValue('create')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('resources')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->info('This is the name that is also used in the url. So to fetch the list of countries you access /countries. You can use any alphanumeric character together with _ and -')
                                ->isRequired()
                            ->end()
                            ->scalarNode('entity_class')
                                ->info('The entity class for this resource.')
                                ->isRequired()
                            ->end()
                            ->arrayNode('excluded_attributes')
                                ->info(
                                    'A list of attributes that you want to exclude for reading. Take in account this is for reading.'.PHP_EOL.
                                    'If you want to exclude writing attributes just do not call your method setMyAttribute(the convention is editMyAttribute)'
                                )
                                ->defaultValue([])
                                ->prototype('scalar')
                                    ->validate()
                                        ->ifInArray(['id'])->thenInvalid('Id can not be excluded')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('attributes_permissions')
                                ->info(
                                    'This is one of the complex configs of this library. You can decide who can see what based on either the role or the state of the object.'.PHP_EOL.
                                    'It\'s a collection of AND condition. It means that each item of this collection defines a list of AND condition and then the result of the items are evaluated together with OR.'.PHP_EOL.
                                    'Each condition itself is an array with 2 elements. First element can be either `byRole` or `byMethod`. In case of `byRole` the second value of condition is the role and in case of `byMethod` it is the method that will be called on the resource.'.PHP_EOL.
                                    'An example of an item of the collection: [[byRole, ROLE_ADMIN], [byMethod, isNotDeleted]]'
                                )
                                ->defaultValue([])
                                ->prototype('array')
                                    ->prototype('array')
                                        ->canNotBeEmpty()
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode(0)
                                                    ->isRequired()
                                                    ->validate()
                                                        ->ifNotInArray(['byRole', 'byMethod'])->thenInvalid('Only byRole or byMethod is accepted')
                                                    ->end()
                                                ->end()
                                                ->scalarNode(1)
                                                    ->isRequired()
                                                    ->canNotBeEmpty()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('virtual_paths')
                                ->info('Virtual paths are used for easier filtering. So instead of company.name you can use directly companyName in the filter path.')
                                ->defaultValue([])
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('virtual_properties')
                                ->info('Virtual properties are a set of key values where the key is the name of the virtual property and the value the method used to fetch the value, The virtual property will appear in the attributes but is not filterable or sortable.')
                                ->defaultValue([])
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('relationships')
                                ->info('The relationships to expose to the api.')
                                ->defaultValue([])
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('type')
                                            ->isRequired()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('repository_service_id')
                                ->info(
                                    'The service id of the repository. The repository has to implement the interface ResourceRepository.'.PHP_EOL.
                                    'If not provided the default doctrine repository of this entity is used. The encapsulator class is DoctrineResourceRepository.'
                                )
                                ->defaultNull()
                            ->end()
                            ->scalarNode('manager_service_id')
                                ->info(
                                    'The id of the manager service. The manager is responsible of creating and updating the entity. It has to implement ResourceManager.'.PHP_EOL.
                                    'The default value for this config is trivago_jade.generic_resource_manager with class GenericResourceManager.'
                                )
                                ->defaultValue('trivago_jade.generic_resource_manager')
                            ->end()
                            ->scalarNode('parent')
                                ->info('With this attribute the resource will inherit the following values of the parent: '.implode(', ', ResourceConfigProvider::INHERITABLE_FIELDS))
                                ->defaultNull()
                            ->end()
                            ->arrayNode('value_objects')
                                ->info('If your setter or create method needs a value object instead of the plain value you can use this option')
                                ->defaultValue([])
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('allowed_actions')
                                ->info('The actions that are allowed on this entity. If nothing specified the entity can only be read.')
                                ->prototype('scalar')
                                    ->validate()
                                        ->ifNotInArray([
                                            ResourceConfig::ACTION_CREATE,
                                            ResourceConfig::ACTION_UPDATE,
                                            ResourceConfig::ACTION_DELETE,
                                        ])
                                        ->thenInvalid('The actions can be only create, update or delete')
                                    ->end()
                                ->end()
                                ->defaultValue([])
                            ->end()
                            ->arrayNode('roles')
                                ->info('This will rewrite the default role mentioned above.')
                                ->children()
                                    ->scalarNode('create')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('update')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('read')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('delete')
                                        ->defaultNull()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
