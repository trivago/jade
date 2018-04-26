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

use Trivago\Jade\Application\JsonApi\Request\CollectionRequest;
use Trivago\Jade\Application\JsonApi\Request\CreateRequest;
use Trivago\Jade\Application\JsonApi\Request\DeleteRequest;
use Trivago\Jade\Application\JsonApi\Request\EntityRequest;
use Trivago\Jade\Application\JsonApi\Request\UpdateRequest;

interface RequestListener
{
    /**
     * @param EntityRequest $request
     *
     * @return EntityRequest
     */
    public function onGetEntityRequest(EntityRequest $request);

    /**
     * @param CollectionRequest $request
     *
     * @return CollectionRequest
     */
    public function onGetCollectionRequest(CollectionRequest $request);

    /**
     * @param CreateRequest $request
     *
     * @return CreateRequest
     */
    public function onCreateRequest(CreateRequest $request);

    /**
     * @param UpdateRequest $request
     *
     * @return UpdateRequest
    */
    public function onUpdateRequest(UpdateRequest $request);

    /**
     * @param DeleteRequest $request
     *
     * @return DeleteRequest
    */
    public function onDeleteRequest(DeleteRequest $request);

    /**
     * @param string $resourceName
     *
     * @return bool
     */
    public function supports($resourceName);
}
