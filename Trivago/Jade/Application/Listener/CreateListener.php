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

interface CreateListener
{
    /**
     * The object instance is created but not saved yet.
     *
     * @param object $entity
     *
     * @deprecated Use beforeSave instead
     */
    public function beforeCreate($entity);

    /**
     * @param object $entity
     */
    public function beforeSave($entity);

    /**
     * The object instance is created and saved.
     *
     * @param object $entity
     *
     * @deprecated Use afterSave instead
     */
    public function afterCreate($entity);

    /**
     * @param object $entity
     */
    public function afterSave($entity);

    /**
     * @param string $resourceName
     *
     * @return bool
     */
    public function supports($resourceName);
}
