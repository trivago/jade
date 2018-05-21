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

class InvalidRequest extends \RuntimeException
{
    /**
     * @var Error[]
     */
    private $errors;

    /**
     * @param Error[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct(
            implode(
                ';',
                array_map(
                    function (Error $error) {
                        return sprintf ('id: %s, code: %s, title: %s', $error->getId(), $error->getCode(), $error->getTitle());
                    },
                    $this->errors
                )
            )
        );
    }

    /**
     * @param string $id
     * @param string $code
     * @param string $message
     *
     * @return InvalidRequest
     */
    public static function createWithMessage($id, $code, $message)
    {
        return new self([new Error($id, null, null, $code, $message)]);
    }

    /**
     * @param string $id
     * @param string $code
     * @param string $message
     *
     * @throws InvalidRequest
     */
    public static function throwWithMessage($id, $code, $message)
    {
        throw self::createWithMessage($id, $code, $message);
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
