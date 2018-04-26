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

namespace Trivago\Jade\Application\JsonApi\Schema;

use Neomerx\JsonApi\Document\Error;

class RequestValidator
{
    /**
     * @param array  $data
     * @param string $resourceName
     * @param mixed  $id
     *
     * @throws InvalidRequest
     */
    public function validateUpdateRequest(array $data, $resourceName, $id)
    {
        $errors = $this->validateBasic($data, $resourceName);
        if (!isset($data['data']['id']) || $data['data']['id'] != $id) {
            $errors[] = new Error('data.id', null, null, 'invalid_format', 'Missing or invalid key data.id');
        }
        if (count($errors)) {
            throw new InvalidRequest($errors);
        }
    }

    /**
     * @param array  $data
     * @param string $resourceName
     *
     * @throws InvalidRequest
     */
    public function validateCreateRequest(array $data, $resourceName)
    {
        $errors = $this->validateBasic($data, $resourceName);
        if (isset($data['data']['id'])) {
            $errors[] = new Error('data.id', null, null, 'invalid_format', 'data.id can not be present for create');
        }

        if (count($errors)) {
            throw new InvalidRequest($errors);
        }
    }

    /**
     * @param array  $data
     * @param string $resourceName
     *
     * @return array
     */
    private function validateBasic($data, $resourceName)
    {
        $errors = [];
        if (!isset($data['data'])) {
            $errors[] = new Error('data', null, null, 'invalid_format', 'Missing key data');

            return $errors;
        }

        if (!isset($data['data']['type']) || $data['data']['type'] !== $resourceName) {
            $errors[] = new Error('data.type', null, null, 'invalid_value', 'Missing or invalid key data.type');
        }

        if (isset($data['data']['attributes']) && !is_array($data['data']['attributes'])) {
            $errors[] = new Error('data.attributes', null, null, 'invalid_format', 'Invalid format for key data.attributes');
        }

        if (isset($data['data']['attributes']['id'])) {
            $errors[] = new Error('data.attributes', null, null, 'invalid_format', 'Attributes can not contain id.');
        }

        return $errors;
    }
}
