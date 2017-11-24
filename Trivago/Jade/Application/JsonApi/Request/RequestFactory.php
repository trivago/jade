<?php

/*
 * Copyright (c) 2017 trivago
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
 *
 * @author Moein Akbarof <moein.akbarof@trivago.com>
 * @date 2017-09-10
 */

namespace Trivago\Jade\Application\JsonApi\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;
use Trivago\Jade\Application\JsonApi\Schema\InvalidRequest;
use Trivago\Jade\Application\JsonApi\Schema\RequestValidator;
use Trivago\Jade\Domain\Resource\CompositeFilter;
use Trivago\Jade\Domain\Resource\CompositeFilterTypes;
use Trivago\Jade\Domain\Resource\Constraint;
use Trivago\Jade\Domain\Resource\ExpressionFilter;
use Trivago\Jade\Domain\Resource\Path;
use Trivago\Jade\Domain\Resource\Sort;
use Trivago\Jade\Domain\Resource\SortCollection;
use Trivago\Jade\Domain\Resource\SortDirections;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceAttributeBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ToManyRelationship;
use Trivago\Jade\Domain\ResourceManager\Bag\ToOneRelationship;

class RequestFactory
{
    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * @var ResourceConfigProvider
     */
    private $resourceConfigProvider;

    /**
     * @var int
     */
    private $maxPerPage;

    /**
     * @var int
     */
    private $defaultPerPage;

    /**
     * @param RequestValidator $requestValidator
     * @param ResourceConfigProvider $resourceConfigProvider
     * @param int $maxPerPage
     * @param int $defaultPerPage
     */
    public function __construct(
        RequestValidator $requestValidator,
        ResourceConfigProvider $resourceConfigProvider,
        $maxPerPage,
        $defaultPerPage
    ) {
        $this->requestValidator = $requestValidator;
        $this->resourceConfigProvider = $resourceConfigProvider;
        $this->maxPerPage = $maxPerPage;
        $this->defaultPerPage = $defaultPerPage;
    }

    /**
     * @param ParameterBag $query
     * @param string $resourceName
     * @return CollectionRequest
     */
    public function createCollectionRequest(ParameterBag $query, $resourceName)
    {
        // To be implemented
        $fields = [];
        $constraint = new Constraint();
        $defaultPage = ['number' => 1, 'size' => $this->defaultPerPage];
        $page = array_merge($defaultPage, $query->get('page', []));
        $defaultPage['size'] = min($defaultPage['size'], $this->maxPerPage);
        $constraint->setPerPage($page['size']);
        $constraint->setPageNumber($page['number']);

        $resourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceName);
        $relationships = $this->getRelationships($resourceName, $query->get('include', ''));
        $rawFilters = $query->get('filter', '[]');
        if (!is_string($rawFilters)) {
            throw InvalidRequest::createWithMessage('data.filter', 'invalid_format', 'Filter has to be json string.');
        }
        $rawFilters = \json_decode($rawFilters, true);
        if (!is_array($rawFilters)) {
            throw InvalidRequest::createWithMessage('data.filter', 'invalid_format', 'Invalid json format passed for filter.');
        }
        $this->parseFilters($resourceConfig, $constraint->getFilterCollection(), $rawFilters, ['filter']);

        $this->validateFiltersOnRelationship($constraint->getFilters(), $relationships);

        $this->validatePaths($resourceName, 'data.filter', $constraint->getFilterCollection()->getAllPaths(), false);

        $sortCollection = $this->parseSorts($resourceConfig, $query->get('sort', ''));

        return new CollectionRequest($resourceName, $fields, $relationships, $constraint, $sortCollection);
    }

    /**
     * @param ParameterBag $query
     * @param string $resourceName
     * @param mixed $resourceId
     * @return EntityRequest
     */
    public function createEntityRequest(ParameterBag $query, $resourceName, $resourceId)
    {
        $fields = [];
        $relationships = $this->getRelationships($resourceName, $query->get('include', ''));

        return new EntityRequest($resourceName, $resourceId, $fields, $relationships);
    }

    /**
     * @param string $resourceName
     * @param mixed $resourceId
     * @return DeleteRequest
     */
    public function createDeleteRequest($resourceName, $resourceId)
    {
        return new DeleteRequest($resourceName, $resourceId);
    }

    /**
     * @param Request $request
     * @param string $resourceName
     * @param mixed $resourceId
     * @return UpdateRequest
     */
    public function createUpdateRequest(Request $request, $resourceName, $resourceId)
    {
        $this->prepareRequest($request);
        $this->requestValidator->validateUpdateRequest($request->request->all(), $resourceName, $resourceId);

        $request = new UpdateRequest(
            $resourceName,
            $resourceId,
            $this->createAttributeBag($resourceName, $request->request->get('data')),
            $this->createRelationshipBag($request->request->get('data'), $request->request->get('data_as_object'))
        );

        if (!count($request->getRelationships()->getAllRelationshipNames()) && !count($request->getAttributes()->getAllAttributeNames())) {
            throw InvalidRequest::createWithMessage('data', 'invalid_format', 'For update you have to provide at least one attribute or one relationship');
        }

        return $request;
    }

    /**
     * @param Request $request
     * @param string $resourceName
     * @return CreateRequest
     */
    public function createCreateRequest(Request $request, $resourceName)
    {
        $this->prepareRequest($request);
        $this->requestValidator->validateCreateRequest($request->request->all(), $resourceName);

        return new CreateRequest(
            $resourceName,
            $this->createAttributeBag($resourceName, $request->get('data')),
            $this->createRelationshipBag($request->get('data'), $request->request->get('data_as_object'))
        );
    }

    /**
     * @param Request $request
     */
    private function prepareRequest(Request $request)
    {
        $requestContent = $request->getContent();
        $requestBody = \json_decode($requestContent, true);
        if (!is_array($requestBody)) {
            throw InvalidRequest::createWithMessage('data.id', 'invalid_format', 'The body is not a valid json');
        }

        $requestBody['data_as_object'] = \json_decode($requestContent)->data;

        $request->request->replace($requestBody);
    }

    /**
     * @param string $resourceName
     * @param array $data
     * @return ResourceAttributeBag
     */
    private function createAttributeBag($resourceName, $data)
    {
        $attributes = isset($data['attributes']) ? $data['attributes'] : [];
        $resourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceName);

        foreach ($attributes as $attributeName => $attributeValue) {
            if (!$resourceConfig->hasValueObject($attributeName)) {
                continue;
            }

            $class = $resourceConfig->getValueObjectFor($attributeName);
            $attributes[$attributeName] = null === $attributeValue ? null : new $class($attributeValue);
        }

        return new ResourceAttributeBag($attributes);
    }

    /**
     * @param ResourceConfig $resourceConfig
     * @param string $rawSorts
     * @return SortCollection
     */
    private function parseSorts(ResourceConfig $resourceConfig, $rawSorts)
    {
        $sortCollection = new SortCollection();
        $sorts = array_filter(explode(',', $rawSorts));
        foreach ($sorts as $sort) {
            $direction = SortDirections::ASC;
            if ($sort[0] === '-') {
                $direction = SortDirections::DESC;
                $path = substr($sort, 1);
            } else {
                $path = $sort;
            }

            $sortCollection->addSort(new Sort($resourceConfig->getRealPath($path), $direction));
        }

        return $sortCollection;
    }

    /**
     * @param ResourceConfig $resourceConfig
     * @param CompositeFilter $compositeFilter
     * @param array $rawFilters
     * @param array $errorKey
     */
    private function parseFilters(ResourceConfig $resourceConfig, CompositeFilter $compositeFilter, $rawFilters, $errorKey)
    {
        foreach ($rawFilters as $key => $rawFilter) {
            $currentErrorKey = $errorKey;
            $currentErrorKey[] = $key;

            if (in_array($rawFilter['type'], [CompositeFilterTypes::AND_EXPRESSION, CompositeFilterTypes::OR_EXPRESSION])) {
                $this->validateCompositeFilter(implode('.', $currentErrorKey), $rawFilter);
                $filter = new CompositeFilter($rawFilter['type'], []);
                $compositeFilter->addFilter($filter);
                $currentErrorKey[] = 'filters';
                $this->parseFilters($resourceConfig, $filter, $rawFilter['filters'], $errorKey);
            } else {
                $this->validateExpressionFilter(implode('.', $currentErrorKey), $rawFilter);
                $compositeFilter->addFilter(new ExpressionFilter(
                    $resourceConfig->getRealPath($rawFilter['path']),
                    $rawFilter['type'],
                    $rawFilter['value']
                ));
            }
        }
    }

    /**
     * @param $errorKey
     * @param array $rawFilter
     */
    private function validateExpressionFilter($errorKey, $rawFilter)
    {
        $this->validateKeys($errorKey, $rawFilter, ['type', 'value', 'path']);
        try {
            ExpressionFilter::validate($rawFilter['type'], $rawFilter['value']);
        } catch (\InvalidArgumentException $e) {
            throw InvalidRequest::createWithMessage('data.'.$errorKey, 'invalid_format', $e->getMessage());
        }
    }

    /**
     * @param $errorKey
     * @param array $rawFilter
     */
    private function validateCompositeFilter($errorKey, $rawFilter)
    {
        $this->validateKeys($errorKey, $rawFilter, ['type', 'filters']);
        if (!CompositeFilterTypes::isValid($rawFilter['type'])) {
            throw InvalidRequest::createWithMessage('data.'.$errorKey.'.type', 'invalid_format', sprintf('Invalid filter type %s passed', $rawFilter['type']));
        }
        if (!is_array($rawFilter['filters'])) {
            throw InvalidRequest::createWithMessage('data.'.$errorKey.'.filters', 'invalid_format', 'Key "filters" should be an array');
        }
    }

    /**
     * @param string $errorKey
     * @param array $array
     * @param array $keys
     */
    private function validateKeys($errorKey, $array, $keys)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw InvalidRequest::createWithMessage('data.'.$errorKey, 'invalid_format', sprintf('Missing key %s in %s', $key, $errorKey));
            }
        }
    }

    /**
     * @param array $data
     * @param \stdClass $dataAsObject
     * @return ResourceRelationshipBag
     */
    private function createRelationshipBag($data, $dataAsObject)
    {
        $resourceConfig = $this->resourceConfigProvider->getResourceConfig($data['type']);
        $rawRelationships = isset($data['relationships']) ? $data['relationships'] : [];

        $relationships = [];
        foreach ($rawRelationships as $relationshipName => $rawRelationship) {
            if (!$resourceConfig->hasRelationship($relationshipName)) {
                throw InvalidRequest::createWithMessage('data.relationships.'.$relationshipName, 'invalid_path', sprintf('No relationship on %s called %s', $data['type'], $relationshipName));
            }
            if (!isset($rawRelationship['data'])) {
                throw InvalidRequest::createWithMessage('data.relationships.'.$relationshipName, 'invalid_format', 'Missing data key in relationship '.$relationshipName);
            }
            $rawRelationship = $rawRelationship['data'];
            $relationshipType = $this->resourceConfigProvider->getResourceConfig($data['type'])->getRelationship($relationshipName)->getType();
            $entityClass = $this->resourceConfigProvider->getResourceConfig($relationshipType)->getEntityClass();
            if (isset($dataAsObject->relationships->$relationshipName->data) && is_object($dataAsObject->relationships->$relationshipName->data)) {
                if (!isset($rawRelationship['id'])) {
                    throw InvalidRequest::createWithMessage('data.relationships.'.$relationshipName.'.data.id', 'invalid_value', 'Missing id of a relationship '.$relationshipName);
                }
                $id = $rawRelationship['id'];
                $this->validateRelationshipType(
                    $rawRelationship,
                    $relationshipName,
                    'data.relationships.'.$relationshipName.'.data.type'
                );
                $relationships[$relationshipName] = new ToOneRelationship($relationshipType, $entityClass, $id);
                continue;
            }
            $ids = [];
            foreach ($rawRelationship as $key => $singleRelationship) {
               $this->validateRelationshipType(
                   $singleRelationship,
                   $relationshipName,
                   sprintf('data.relationships.%s.data[%d].type', $relationshipName, $key)
               );

                $ids[] = $singleRelationship['id'];
            }
            $relationships[$relationshipName] = new ToManyRelationship($relationshipType, $entityClass, $ids);
        }

        return new ResourceRelationshipBag($relationships);
    }

    /**
     * @param array $relationshipData
     * @param string $relationshipName
     * @param string $relationshipPath
     */
    private function validateRelationshipType($relationshipData, $relationshipName, $relationshipPath)
    {
        if (!isset($relationshipData['type']) || !$this->resourceConfigProvider->getResourceConfig($relationshipData['type'])->isTypeValid($relationshipData['type'])) {
            throw InvalidRequest::createWithMessage(
                $relationshipPath,
                'invalid_format',
                'Invalid type for the relationship '
                    .$relationshipName
                    .'. Valid value(s) are '
                    .implode(',', $this->resourceConfigProvider->getResourceConfig($relationshipData['type'])->getValidTypes())
            );
        }
    }

    /**
     * @param string $resourceName
     * @param string $includeString
     * @return array
     */
    private function getRelationships($resourceName, $includeString)
    {
        $includes = array_unique(array_filter(explode(',', $includeString)));
        $paths = [];
        foreach ($includes as $include) {
            $paths[] = new Path($include);
        }
        $this->validatePaths($resourceName, 'data.include', $paths, true);

        return $includes;
    }

    /**
     * @param string $resourceName
     * @param string $key
     * @param Path[] $paths
     * @param bool $canBeRelationship
     */
    private function validatePaths($resourceName, $key, array $paths, $canBeRelationship)
    {
        $rootResourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceName);
        foreach ($paths as $path) {
            $resourceConfig = $rootResourceConfig;
            foreach ($path->getRelationshipChain() as $relationshipName) {
                if (!$resourceConfig->hasRelationship($relationshipName)) {
                    throw InvalidRequest::createWithMessage($key, 'invalid_path', 'There is no relationship called '.$relationshipName);
                }
                $resourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceConfig->getRelationship($relationshipName)->getType());
            }
            $reflectionClass = new \ReflectionClass($resourceConfig->getEntityClass());
            if (!$reflectionClass->hasProperty($path->getColumnName())) {
                throw InvalidRequest::createWithMessage($key, 'invalid_path', 'There is no path called '. $path->getFullPath());
            }

            if (!$canBeRelationship && $resourceConfig->hasRelationship($path->getColumnName())) {
                throw InvalidRequest::createWithMessage($key, 'invalid_path', 'The path can not be a relationship. Received '.$path->getFullPath());
            }
        }
    }

    /**
     * @param ExpressionFilter[] $filters
     * @param Path[] $relationships
     */
    private function validateFiltersOnRelationship(array $filters, array $relationships)
    {
        $possiblePaths = [];
        foreach ($relationships as $relationship) {
            $path = new Path($relationship);
            $possiblePaths = array_merge($possiblePaths, $path->generateAllPossiblePaths());
        }

        foreach ($filters as $key => $filter) {
            if ($filter instanceof CompositeFilter) {
                $this->validateFiltersOnRelationship($filter->getFilters(), $relationships);
                continue;
            }
            $resourcePath = $filter->getPath()->getResourcePath();
            if (!$resourcePath) {
                continue;
            }
            if (array_search($resourcePath, $possiblePaths) === false) {
                InvalidRequest::throwWithMessage(sprintf('data.filters[%d].path', $key), 'invalid_filter_path', 'You have to include the relationship to be able to filter with it. Missing '.$resourcePath);
            }
        }
    }
}
