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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Trivago\Jade\Application\Listener\CreateListener;
use Trivago\Jade\Application\Listener\DeleteListener;
use Trivago\Jade\Application\Listener\ExceptionListener;
use Trivago\Jade\Application\Listener\ManipulationListener;
use Trivago\Jade\Application\Listener\RequestListener;
use Trivago\Jade\Application\Listener\ResponseListener;
use Trivago\Jade\Application\Listener\UpdateListener;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TrivagoJadeExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('json_api_debug', $config['debug']);
        $container->setParameter('json_api_default_manipulate_role', $config['security']['default_manipulate_role']);
        $container->setParameter('json_api_default_read_role', $config['security']['default_read_role']);
        $container->setParameter('json_api_security_enabled', $config['security']['enabled']);
        $container->setParameter('json_api_strict_filtering_and_sorting', $config['security']['strict_filtering_and_sorting']);
        $container->setParameter('json_api_max_per_page', $config['read']['max_per_page']);
        $container->setParameter('json_api_default_per_page', $config['read']['default_per_page']);
        $container->setParameter('json_api_fetch_total_count', $config['read']['fetch_total_count']);
        $container->setParameter('json_api_max_relationship_depth', $config['read']['max_relationship_depth']);
        $container->setParameter('json_api_manipulate_include_relationships', $config['manipulate']['include_relationships']);
        $container->setParameter('json_api_on_update_method', $config['manipulate']['on_update_method']);
        $container->setParameter('json_api_create_method', $config['manipulate']['create_method']);

        if ($container->hasParameter('doctrine.orm.proxy_namespace')) {
            $proxyNamespace = $container->getParameter('doctrine.orm.proxy_namespace');
            foreach ($config['resources'] as &$resource) {
                $resource['entity_class_aliases'] = [$proxyNamespace.'\\__CG__\\'.ltrim($resource['entity_class'], '\\')];
            }
        }
        $container->setParameter('json_api_resources', $config['resources']);

        // For symfony >= 3.2
        if (method_exists($container, 'registerForAutoconfiguration')) {
            $container->registerForAutoconfiguration(ManipulationListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(CreateListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(UpdateListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(DeleteListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(RequestListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(ExceptionListener::class)
                ->addTag('trivago_jade.listener');
            $container->registerForAutoconfiguration(ResponseListener::class)
                ->addTag('trivago_jade.listener');
        }

    }
}
