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

namespace Trivago\Jade\Application\Framework\JadeBundle\Routing;

use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class JsonApiLoader extends Loader
{
    /**
     * @var Resource[]
     */
    private $resourceConfigProvider;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var string
     */
    private $prefix = 'json_api_';

    /**
     * @param ResourceConfigProvider $resourceConfigProvider
     */
    public function __construct(ResourceConfigProvider $resourceConfigProvider)
    {
        $this->resourceConfigProvider = $resourceConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->resourceConfigProvider->getResourceConfigs() as $resourceConfig) {
            /** @var ResourceConfig $resourceConfig */
            $routes->add($this->prefix.'get_'.$resourceConfig->getName().'_collection', $this->getCollectionPath($resourceConfig));
            $routes->add($this->prefix.'get_'.$resourceConfig->getName().'_single', $this->getEntityPath($resourceConfig));
            if ($resourceConfig->isCreateAllowed()) {
                $routes->add($this->prefix.'create_'.$resourceConfig->getName(), $this->getCreatePath($resourceConfig));
            }
            if ($resourceConfig->isUpdateAllowed()) {
                $routes->add($this->prefix.'update_'.$resourceConfig->getName(), $this->getUpdatePath($resourceConfig));
            }
            if ($resourceConfig->isDeleteAllowed()) {
                $routes->add($this->prefix.'delete_'.$resourceConfig->getName(), $this->getDeletePath($resourceConfig));
            }
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'json_api' === $type;
    }

    /**
     * @param ResourceConfig $resourceConfig
     *
     * @return Route
     */
    private function getEntityPath(ResourceConfig $resourceConfig)
    {
        return $this->getPath($resourceConfig, 'getEntity', '/'.$resourceConfig->getName().'/{id}', Request::METHOD_GET);
    }

    /**
     * @param ResourceConfig $resourceConfig
     *
     * @return Route
     */
    private function getCollectionPath(ResourceConfig $resourceConfig)
    {
        return $this->getPath($resourceConfig, 'getCollection', '/'.$resourceConfig->getName(), Request::METHOD_GET);
    }

    /**
     * @param ResourceConfig $resourceConfig
     *
     * @return Route
     */
    private function getCreatePath(ResourceConfig $resourceConfig)
    {
        return $this->getPath($resourceConfig, 'createEntity', '/'.$resourceConfig->getName(), Request::METHOD_POST);
    }

    /**
     * @param ResourceConfig $resourceConfig
     *
     * @return Route
     */
    private function getUpdatePath(ResourceConfig $resourceConfig)
    {
        return $this->getPath($resourceConfig, 'updateEntity', '/'.$resourceConfig->getName().'/{id}', Request::METHOD_PATCH);
    }

    /**
     * @param ResourceConfig $resourceConfig
     *
     * @return Route
     */
    private function getDeletePath(ResourceConfig $resourceConfig)
    {
        return $this->getPath($resourceConfig, 'deleteEntity', '/'.$resourceConfig->getName().'/{id}', Request::METHOD_DELETE);
    }

    /**
     * @param ResourceConfig $resourceConfig
     * @param string         $controllerAction
     * @param string         $path
     * @param string         $method
     *
     * @return Route
     */
    private function getPath(ResourceConfig $resourceConfig, $controllerAction, $path, $method)
    {
        $resourceName = $resourceConfig->getName();
        $defaults = [
            '_controller' => 'trivago_jade.json_api_controller:'.$controllerAction.'Action',
            'resourceName' => $resourceName,
        ];

        $route = new Route($path, $defaults);
        $route->setMethods([$method]);

        return $route;
    }
}
