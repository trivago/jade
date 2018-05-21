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

namespace Trivago\Jade\Application\Framework\JadeBundle\ResourceRepository;

use Doctrine\ORM\EntityRepository;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepository;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepositoryProvider as ResourceRepositoryProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Trivago\Jade\Infrastructure\Repository\DoctrineResourceRepository;

class ResourceRepositoryProvider implements ResourceRepositoryProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var  Registry
     */
    private $doctrine;

    /**
     * @var ResourceConfig[]
     */
    private $resourceConfigs = [];

    /**
     * @param ContainerInterface     $container
     * @param Registry               $doctrine
     * @param ResourceConfigProvider $resourceConfigProvider
     */
    public function __construct(
        ContainerInterface $container,
        Registry $doctrine,
        ResourceConfigProvider $resourceConfigProvider
    ) {
        $this->container = $container;
        $this->doctrine = $doctrine;

        foreach ($resourceConfigProvider->getResourceConfigs() as $resourceConfig) {
            $this->resourceConfigs[$resourceConfig->getEntityClass()] = $resourceConfig;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($entityClass)
    {
        if (!isset($this->resourceConfigs[$entityClass])) {
            throw new \LogicException(sprintf('No entity class "%s" defined in the resource configs.', $entityClass));
        }

        $resourceConfig = $this->resourceConfigs[$entityClass];
        if ($resourceConfig->getRepositoryServiceId()) {
            $repository = $this->container->get($resourceConfig->getRepositoryServiceId());
            if (!$repository instanceof ResourceRepository) {
                throw new \LogicException('If you define your own repository it has to implements '.ResourceRepository::class);
            }
            return $repository;
        }

        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository($entityClass);

        return new DoctrineResourceRepository($this->doctrine, $repository);
    }
}
