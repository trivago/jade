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

namespace Trivago\Jade\Application\Framework\JadeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Trivago\Jade\Application\Listener\CreateListener;
use Trivago\Jade\Application\Listener\DeleteListener;
use Trivago\Jade\Application\Listener\ExceptionListener;
use Trivago\Jade\Application\Listener\RequestListener;
use Trivago\Jade\Application\Listener\ResponseListener;
use Trivago\Jade\Application\Listener\UpdateListener;

class ListenerPass implements CompilerPassInterface
{
    const TAG = 'trivago_jade.listener';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     * @throws ServiceNotFoundException
     */
    public function process(ContainerBuilder $container)
    {
        $listenerManagerDefinition = $container->findDefinition('trivago_jade.listener_manager');

        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        foreach ($taggedServices as $taggedServiceId => $tags) {
            $taggedServiceDefinition = $container->findDefinition($taggedServiceId);
            $classReflection = new \ReflectionClass($taggedServiceDefinition->getClass());

            //Adding the same tag twice should put the listener twice in the ListenerManager
            foreach ($tags as $attributes) {
                $priority = 0;
                if (isset($attributes['priority'])) {
                    $priority = $attributes['priority'];
                }
                if ($classReflection->implementsInterface(RequestListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addRequestListener', [new Reference($taggedServiceId), $priority]);
                }
                if ($classReflection->implementsInterface(ResponseListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addResponseListener', [new Reference($taggedServiceId), $priority]);
                }
                if ($classReflection->implementsInterface(CreateListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addCreateListener', [new Reference($taggedServiceId), $priority]);
                }
                if ($classReflection->implementsInterface(UpdateListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addUpdateListener', [new Reference($taggedServiceId), $priority]);
                }
                if ($classReflection->implementsInterface(DeleteListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addDeleteListener', [new Reference($taggedServiceId), $priority]);
                }
                if ($classReflection->implementsInterface(ExceptionListener::class)) {
                    $listenerManagerDefinition->addMethodCall('addExceptionListener', [new Reference($taggedServiceId), $priority]);
                }
            }
        }
    }
}
