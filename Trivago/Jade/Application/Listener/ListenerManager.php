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

namespace Trivago\Jade\Application\Listener;

use Trivago\Jade\Application\JsonApi\Error\ErrorInterface;
use Trivago\Jade\Application\JsonApi\Request\CollectionRequest;
use Trivago\Jade\Application\JsonApi\Request\CreateRequest;
use Trivago\Jade\Application\JsonApi\Request\DeleteRequest;
use Trivago\Jade\Application\JsonApi\Request\EntityRequest;
use Trivago\Jade\Application\JsonApi\Request\UpdateRequest;

class ListenerManager
{
    /**
     * Key is the priority and the values.
     *
     * @var RequestListener[][]
     */
    private $requestListeners = [];

    /**
     * Key is the priority and the values.
     *
     * @var ResponseListener[][]
     */
    private $responseListeners = [];

    /**
     * Key is the priority and the values.
     *
     * @var CreateListener[][]
     */
    private $createListeners = [];

    /**
     * Key is the priority and the values.
     *
     * @var UpdateListener[][]
     */
    private $updateListeners = [];

    /**
     * Key is the priority and the values.
     *
     * @var DeleteListener[][]
     */
    private $deleteListeners = [];

    /**
     * Key is the priority and the values.
     *
     * @var ExceptionListener[][]
     */
    private $exceptionListeners = [];

    /**
     * @param RequestListener $requestListener
     * @param int             $priority
     */
    public function addRequestListener(RequestListener $requestListener, $priority)
    {
        $this->requestListeners[$priority][] = $requestListener;
    }

    /**
     * @param ResponseListener $responseListener
     * @param int              $priority
     */
    public function addResponseListener(ResponseListener $responseListener, $priority)
    {
        $this->responseListeners[$priority][] = $responseListener;
    }

    /**
     * @param CreateListener $createListener
     * @param int            $priority
     */
    public function addCreateListener(CreateListener $createListener, $priority)
    {
        $this->createListeners[$priority][] = $createListener;
    }

    /**
     * @param UpdateListener $updateListener
     * @param int            $priority
     */
    public function addUpdateListener(UpdateListener $updateListener, $priority)
    {
        $this->updateListeners[$priority][] = $updateListener;
    }

    /**
     * @param DeleteListener $deleteListener
     * @param int            $priority
     */
    public function addDeleteListener(DeleteListener $deleteListener, $priority)
    {
        $this->deleteListeners[$priority][] = $deleteListener;
    }

    /**
     * @param ExceptionListener $exceptionListener
     * @param int               $priority
     */
    public function addExceptionListener(ExceptionListener $exceptionListener, $priority)
    {
        $this->exceptionListeners[$priority][] = $exceptionListener;
    }

    /**
     * @return RequestListener[]
     */
    public function getRequestListeners()
    {
        return $this->flattenListeners($this->requestListeners);
    }

    /**
     * @return ResponseListener[]
     */
    public function getResponseListeners()
    {
        return $this->flattenListeners($this->responseListeners);
    }

    /**
     * @return CreateListener[]
     */
    public function getCreateListeners()
    {
        return $this->flattenListeners($this->createListeners);
    }

    /**
     * @return UpdateListener[]
     */
    public function getUpdateListeners()
    {
        return $this->flattenListeners($this->updateListeners);
    }

    /**
     * @return DeleteListener[]
     */
    public function getDeleteListeners()
    {
        return $this->flattenListeners($this->deleteListeners);
    }

    /**
     * @return ExceptionListener[]
     */
    public function getExceptionListeners()
    {
        return $this->flattenListeners($this->exceptionListeners);
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function beforeSaveNewResource($entity, $resourceName)
    {
        foreach ($this->getCreateListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->beforeCreate($entity);
            $listener->beforeSave($entity);
        }
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function beforeUpdate($entity, $resourceName)
    {
        foreach ($this->getUpdateListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->beforeUpdate($entity);
        }
    }

    /**
     * @param object $entityId
     * @param string $resourceName
     */
    public function beforeDelete($entityId, $resourceName)
    {
        foreach ($this->getDeleteListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->beforeDelete($entityId);
        }
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function afterSaveNewResource($entity, $resourceName)
    {
        foreach ($this->getCreateListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->afterCreate($entity);
            $listener->afterSave($entity);
        }
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function afterUpdate($entity, $resourceName)
    {
        foreach ($this->getUpdateListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->afterUpdate($entity);
        }
    }

    /**
     * @param mixed  $entityId
     * @param string $resourceName
     */
    public function afterDelete($entityId, $resourceName)
    {
        foreach ($this->getDeleteListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->afterDelete($entityId);
        }
    }

    /**
     * @param EntityRequest $request
     * @param string        $resourceName
     *
     * @return EntityRequest
     */
    public function onGetEntityRequest(EntityRequest $request, $resourceName)
    {
        $toUseRequest = $request;
        foreach ($this->getRequestListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $newRequest = $listener->onGetEntityRequest($toUseRequest);
            if (null === $newRequest) {
                continue;
            }
            if (!$newRequest instanceof EntityRequest) {
                throw new \LogicException('The returned request by listener for onGetEntityRequest should be EntityRequest');
            }

            $toUseRequest = $newRequest;
        }

        return $toUseRequest;
    }

    /**
     * @param CollectionRequest $request
     * @param string            $resourceName
     *
     * @return CollectionRequest
     */
    public function onGetCollectionRequest(CollectionRequest $request, $resourceName)
    {
        $toUseRequest = $request;
        foreach ($this->getRequestListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $newRequest = $listener->onGetCollectionRequest($toUseRequest);
            if (null === $newRequest) {
                continue;
            }
            if (!$newRequest instanceof CollectionRequest) {
                throw new \LogicException('The returned request by listener for onGetCollectionRequest should be CollectionRequest');
            }

            $toUseRequest = $newRequest;
        }

        return $toUseRequest;
    }

    /**
     * @param CreateRequest $request
     * @param string        $resourceName
     *
     * @return CreateRequest
     */
    public function onCreateRequest(CreateRequest $request, $resourceName)
    {
        $toUseRequest = $request;
        foreach ($this->getRequestListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $newRequest = $listener->onCreateRequest($toUseRequest);
            if (null === $newRequest) {
                continue;
            }
            if (!$newRequest instanceof CreateRequest) {
                throw new \LogicException('The returned request by listener for onCreateRequest should be CreateRequest');
            }

            $toUseRequest = $newRequest;
        }

        return $toUseRequest;
    }

    /**
     * @param UpdateRequest $request
     * @param string        $resourceName
     *
     * @return UpdateRequest
     */
    public function onUpdateRequest(UpdateRequest $request, $resourceName)
    {
        $toUseRequest = $request;
        foreach ($this->getRequestListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $newRequest = $listener->onUpdateRequest($toUseRequest);
            if (null === $newRequest) {
                continue;
            }
            if (!$newRequest instanceof UpdateRequest) {
                throw new \LogicException('The returned request by listener for onUpdateRequest should be UpdateRequest');
            }

            $toUseRequest = $newRequest;
        }

        return $toUseRequest;
    }

    /**
     * @param DeleteRequest $request
     * @param string        $resourceName
     *
     * @return DeleteRequest
     */
    public function onDeleteRequest(DeleteRequest $request, $resourceName)
    {
        $toUseRequest = $request;
        foreach ($this->getRequestListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $newRequest = $listener->onDeleteRequest($toUseRequest);
            if (null === $newRequest) {
                continue;
            }
            if (!$newRequest instanceof DeleteRequest) {
                throw new \LogicException('The returned request by listener for onUpdateRequest should be UpdateRequest');
            }

            $toUseRequest = $newRequest;
        }

        return $toUseRequest;
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function onGetEntityResponse($entity, $resourceName)
    {
        foreach ($this->getResponseListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->onGetEntityResponse($entity);
        }
    }

    /**
     * @param array $entities
     * @param string $resourceName
     */
    public function onGetCollectionResponse($entities, $resourceName)
    {
        foreach ($this->getResponseListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->onGetCollectionResponse($entities);
        }
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function onCreateResponse($entity, $resourceName)
    {
        foreach ($this->getResponseListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->onCreateResponse($entity);
        }
    }

    /**
     * @param object $entity
     * @param string $resourceName
     */
    public function onUpdateResponse($entity, $resourceName)
    {
        foreach ($this->getResponseListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $listener->onUpdateResponse($entity);
        }
    }

    /**
     * @param \Exception $exception
     * @param string     $resourceName
     *
     * @return null|ErrorInterface[]
     */
    public function onException(\Exception $exception, $resourceName)
    {
        foreach ($this->getExceptionListeners() as $listener) {
            if (!$listener->supports($resourceName)) {
                continue;
            }

            $errors = $listener->onException($exception);
            if (null !== $errors) {
                return $errors;
            }
        }

        return null;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function flattenListeners($array)
    {
        return array_reduce(
            $array,
            function($carry, $item) {
                $carry = array_merge($carry, $item);

                return $carry;
            },
            []
        );
    }
}
