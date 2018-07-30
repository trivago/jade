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

namespace Trivago\Jade\Application\Framework\JadeBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Trivago\Jade\Application\JsonApi\Error\ErrorInterface;
use Trivago\Jade\Application\JsonApi\Request\RequestFactory;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfig;
use Trivago\Jade\Application\JsonApi\Config\ResourceConfigProvider;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Factories\Factory;
use Trivago\Jade\Application\JsonApi\Schema\InvalidRequest;
use Trivago\Jade\Application\Listener\ListenerManager;
use Trivago\Jade\Domain\Resource\Exception\InvalidPath;
use Trivago\Jade\Domain\ResourceManager\Exception\InvalidModelSet;
use Trivago\Jade\Domain\ResourceManager\Exception\ModelException;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceCounter;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepository;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepositoryProvider;
use Trivago\Jade\Domain\ResourceManager\ResourceManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Trivago\Jade\Application\Response\JsonApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Neomerx\JsonApi\Http\Request as JsonApiRequest;
use Trivago\Jade\Domain\Mapping\ResourceMapper;

class JsonApiController extends Controller
{
    const JSON_ENCODE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    /**
     * @var ResourceConfigProvider
     */
    private $resourceConfigProvider;

    /**
     * @var ResourceRepositoryProvider
     */
    private $resourceRepositoryProvider;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var ListenerManager
     */
    private $listenerManager;

    /**
     * @var string
     */
    private $jsonApiBaseUrl;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @param ResourceConfigProvider     $resourceConfigProvider
     * @param ResourceRepositoryProvider $resourceRepositoryProvider
     * @param RequestFactory             $requestFactory
     * @param ListenerManager            $listenerManager
     * @param RequestStack               $requestStack
     */
    public function __construct(
        ResourceConfigProvider $resourceConfigProvider,
        ResourceRepositoryProvider $resourceRepositoryProvider,
        RequestFactory $requestFactory,
        ListenerManager $listenerManager,
        RequestStack $requestStack
    ) {
        $this->resourceConfigProvider = $resourceConfigProvider;
        $this->resourceRepositoryProvider = $resourceRepositoryProvider;
        $this->requestFactory = $requestFactory;
        $this->listenerManager = $listenerManager;
    }

    /**
     * @param string  $resourceName
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    public function getCollectionAction($resourceName, Request $httpRequest)
    {
        $this->setupJsonApiBaseUrl($httpRequest, 1);
        try {
            return $this->getCollection($resourceName, $httpRequest);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $resourceName);
        }
    }

    /**
     * @param string  $resourceName
     * @param mixed   $id
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    public function getEntityAction($resourceName, $id, Request $httpRequest)
    {
        $this->setupJsonApiBaseUrl($httpRequest, 2);
        try {
            return $this->getEntity($resourceName, $id, $httpRequest);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $resourceName);
        }
    }

    /**
     * @param string  $resourceName
     * @param mixed   $id
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    public function deleteEntityAction($resourceName, $id, Request $httpRequest)
    {
        $this->setupJsonApiBaseUrl($httpRequest, 2);
        try {
            return $this->deleteEntity($resourceName, $id);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $resourceName);
        }
    }

    /**
     * @param string  $resourceName
     * @param mixed   $id
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    public function updateEntityAction($resourceName, $id, Request $httpRequest)
    {
        $this->setupJsonApiBaseUrl($httpRequest, 2);
        try {
            return $this->updateEntity($resourceName, $id, $httpRequest);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $resourceName);
        }
    }

    /**
     * @param string  $resourceName
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    public function createEntityAction($resourceName, Request $httpRequest)
    {
        $this->setupJsonApiBaseUrl($httpRequest, 1);
        try {
            return $this->createEntity($resourceName, $httpRequest);
        } catch (\Exception $exception) {
            return $this->handleException($exception, $resourceName);
        }
    }

    /**
     * @param string  $resourceName
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws InvalidPath
     * @throws AccessDeniedException
     * @throws InvalidRequest
     */
    protected function getCollection($resourceName, Request $httpRequest)
    {
        $this->checkAccess($resourceName, ResourceConfig::ACTION_READ);
        $request = $this->requestFactory->createCollectionRequest($httpRequest->query, $resourceName);
        $this->listenerManager->onGetCollectionRequest($request, $resourceName);
        $repository = $this->getRepository($resourceName);
        $entities = $repository->fetchResourcesWithConstraint(
            $request->getRelationships(),
            $request->getConstraint(),
            $request->getSorts()
        );

        $encoder = $this->getEncoder($request->getRelationships());
        $this->listenerManager->onGetCollectionResponse($entities, $resourceName);
        $data = json_decode($encoder->encodeData($entities), true);

        $data['meta'] = [
            'perPage' => $request->getConstraint()->getPerPage(),
            'currentPage' => $request->getConstraint()->getPageNumber(),
        ];

        if ($this->getParameter('json_api_fetch_total_count')) {
            if (!$repository instanceof ResourceCounter) {
                throw new \LogicException(sprintf(
                    'You have enabled fetch_total_count option. The repository for %s should implement ResourceCounter',
                    get_class($repository)
                ));
            }
            $data['meta']['totalItems'] = $repository->countResourcesWithFilters(
                $request->getRelationships(),
                $request->getConstraint()->getFilterCollection()
            );

            $data['meta']['totalPages'] = (int) (abs($data['meta']['totalItems']-1)/$request->getConstraint()->getPerPage() + 1);
            $data['links'] = $this->buildLinks($httpRequest, $request->getConstraint()->getPageNumber(), $data['meta']['totalPages']);
        } else {
            $data['links'] = $this->buildLinks($httpRequest, $request->getConstraint()->getPageNumber(), null);
        }

        $response = new JsonApiResponse($data, JsonApiResponse::HTTP_OK, []);
        if ($this->getParameter('json_api_debug')) {
            $response->setEncodingOptions($response->getEncodingOptions() | self::JSON_ENCODE_FLAGS);
        }

        return $response;
    }

    /**
     * @param string  $resourceName
     * @param mixed   $id
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws InvalidPath
     * @throws AccessDeniedException
     * @throws InvalidRequest
     */
    protected function getEntity($resourceName, $id, Request $httpRequest)
    {
        $request = $this->requestFactory->createEntityRequest($httpRequest->query, $resourceName, $id);
        $this->listenerManager->onGetEntityRequest($request, $resourceName);
        $repository = $this->getRepository($resourceName);
        $entity = $repository->fetchOneResource($id, $request->getRelationships());
        $this->checkAccess($resourceName, ResourceConfig::ACTION_READ, $entity);

        $encoder = $this->getEncoder($request->getRelationships());
        if (!$entity) {
            $error = new Error(null, null, null, 'not_found', sprintf('No %s found with id %s', $resourceName, $id));

            return new JsonApiResponse($encoder->encodeError($error), JsonApiResponse::HTTP_NOT_FOUND, [], true);
        }
        $this->listenerManager->onGetEntityResponse($entity, $resourceName);

        return new JsonApiResponse($encoder->encodeData($entity), JsonApiResponse::HTTP_OK, [], true);
    }

    /**
     * @param string $resourceName
     * @param mixed  $id
     *
     * @return JsonApiResponse
     *
     * @throws AccessDeniedException
     */
    protected function deleteEntity($resourceName, $id)
    {
        $request = $this->requestFactory->createDeleteRequest($resourceName, $id);
        $this->listenerManager->onDeleteRequest($request, $resourceName);
        $repository = $this->getRepository($resourceName);
        $entity = $repository->fetchOneResource($id, []);
        $this->checkAccess($resourceName, ResourceConfig::ACTION_DELETE, $entity);

        $encoder = $this->getEncoder();
        if (!$entity) {
            $error = new Error(null, null, null, 'not_found', sprintf('No %s found with id %s', $resourceName, $id));

            return new JsonApiResponse($encoder->encodeError($error), JsonApiResponse::HTTP_NOT_FOUND, [], true);
        }

        $this->listenerManager->beforeDelete($entity, $resourceName);
        $this->getRepository($resourceName)->deleteResource($entity);
        $this->listenerManager->afterDelete($id, $resourceName);

        return new JsonApiResponse(null, JsonApiResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param string  $resourceName
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws AccessDeniedException
     * @throws InvalidRequest
     */
    protected function createEntity($resourceName, Request $httpRequest)
    {
        $this->checkAccess($resourceName, ResourceConfig::ACTION_CREATE);

        $request = $this->requestFactory->createCreateRequest($httpRequest, $resourceName);
        $request = $this->listenerManager->onCreateRequest($request, $resourceName);

        $resourceManagerServiceId = $this->resourceConfigProvider->getResourceConfig($resourceName)->getManagerServiceId();
        /** @var ResourceManager $manager */
        $manager = $this->get($resourceManagerServiceId);

        $entity = $manager->create(
            $request->getAttributes(),
            $request->getRelationships(),
            $this->resourceConfigProvider->getResourceConfig($resourceName)->getEntityClass()
        );
        $this->listenerManager->beforeSaveNewResource($entity, $resourceName);
        $this->getRepository($resourceName)->saveResource($entity);
        $this->listenerManager->afterSaveNewResource($entity, $resourceName);
        $this->listenerManager->onCreateResponse($entity, $resourceName);

        $includeRelationships = $this->getParameter('json_api_manipulate_include_relationships') ?
            $request->getRelationships()->getAllRelationshipNames() :
            [];

        return new JsonApiResponse(
            $this->getEncoder($includeRelationships)->encodeData($entity),
            JsonApiResponse::HTTP_OK,
            [],
            true);
    }

    /**
     * @param string  $resourceName
     * @param mixed   $id
     * @param Request $httpRequest
     *
     * @return JsonApiResponse
     *
     * @throws InvalidRequest
     * @throws AccessDeniedException
     */
    protected function updateEntity($resourceName, $id, Request $httpRequest)
    {
        $repository = $this->getRepository($resourceName);

        $entity = $repository->fetchOneResource($id, []);

        $this->checkAccess($resourceName, ResourceConfig::ACTION_UPDATE, $entity);

        if (!$entity) {
            $error = new Error(null, null, null, 'not_found', sprintf('No %s found with id %s', $resourceName, $id));

            return new JsonApiResponse($this->getEncoder()->encodeError($error), JsonApiResponse::HTTP_NOT_FOUND, [], true);
        }

        $request = $this->requestFactory->createUpdateRequest($httpRequest, $resourceName, $id);
        $request = $this->listenerManager->onUpdateRequest($request, $resourceName);

        $resourceManagerServiceId = $this->resourceConfigProvider->getResourceConfig($resourceName)->getManagerServiceId();
        /** @var ResourceManager $manager */
        $manager = $this->get($resourceManagerServiceId);

        $manager->update($entity, $request->getAttributes(), $request->getRelationships());

        $this->listenerManager->beforeUpdate($entity, $resourceName);
        $this->getRepository($resourceName)->saveResource($entity);
        $this->listenerManager->afterUpdate($entity, $resourceName);
        $this->listenerManager->onUpdateResponse($entity, $resourceName);

        $includeRelationships = $this->getParameter('json_api_manipulate_include_relationships') ?
            $request->getRelationships()->getAllRelationshipNames() :
            [];

        return new JsonApiResponse(
            $this->getEncoder($includeRelationships)->encodeData($entity),
            JsonApiResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param array $requestedRelationships
     *
     * @return EncoderInterface
     */
    protected function getEncoder(array $requestedRelationships = [])
    {
        if (null !== $this->encoder) {
            return $this->encoder;
        }

        $schemas = [];
        /** @var ResourceMapper $resourceMapper */
        $resourceMapper = $this->get('trivago_jade.resource_mapper');
        foreach ($this->resourceConfigProvider->getResourceConfigs() as $resourceConfig) {
            $schemaClosure = function (FactoryInterface $factory) use ($resourceConfig, $resourceMapper, $requestedRelationships) {
                $schemaProviderClass = $resourceConfig->getSchemaProvider();
                $schema = new $schemaProviderClass(
                    $factory,
                    $resourceConfig,
                    $resourceMapper,
                    $requestedRelationships,
                    $this->jsonApiBaseUrl
                );

                return $schema;
            };

            $schemas[$resourceConfig->getEntityClass()] = $schemaClosure;
            foreach ($resourceConfig->getEntityClassAliases() as $classAlias) {
                $schemas[$classAlias] = $schemaClosure;
            }
        }
        $encoderOptions = $this->getParameter('json_api_debug') ? new EncoderOptions(self::JSON_ENCODE_FLAGS) : null;

        return $this->encoder = Encoder::instance($schemas, $encoderOptions);
    }

    /**
     * @param Request $request
     *
     * @return EncodingParametersInterface
     */
    protected function getJsonApiParameters(Request $request)
    {
        $psr7request = new JsonApiRequest(
            function () use ($request) {
                return $request->getMethod();
            },
            function ($name) use ($request) {
                return $request->headers->get($name);
            },
            function () use ($request) {
                return $request->query->all();
            }
        );

        return (new Factory())->createQueryParametersParser()->parse($psr7request);
    }

    /**
     * @param string $resourceName
     *
     * @return ResourceRepository
     */
    protected function getRepository($resourceName)
    {
        $entityClass = $this->resourceConfigProvider->getResourceConfig($resourceName)->getEntityClass();

        return $this->resourceRepositoryProvider->getRepository($entityClass);
    }

    /**
     * @param string      $resourceName
     * @param string      $action
     *
     * @param null|object $entity
     */
    protected function checkAccess($resourceName, $action, $entity = null)
    {
        if (!$this->getParameter('json_api_security_enabled')) {
            return;
        }
        $resourceConfig = $this->resourceConfigProvider->getResourceConfig($resourceName);
        switch ($action) {
            case ResourceConfig::ACTION_CREATE:
                $role = $resourceConfig->getCreateRole();
                break;
            case ResourceConfig::ACTION_UPDATE:
                $role = $resourceConfig->getUpdateRole();
                break;
            case ResourceConfig::ACTION_DELETE:
                $role = $resourceConfig->getDeleteRole();
                break;
            case ResourceConfig::ACTION_READ:
                $role = $resourceConfig->getReadRole();
                break;
            default:
                throw new \LogicException('Invalid action passed');
        }

        $this->denyAccessUnlessGranted($role, $entity, 'Unable to access this api!');
    }

    /**
     * @param \Exception $exception
     * @param string     $resourceName
     *
     * @return JsonApiResponse
     *
     * @throws \Exception
     */
    protected function handleException(\Exception $exception, $resourceName)
    {
        $errors = $this->listenerManager->onException($exception, $resourceName);
        if ($this->getParameter('json_api_debug')) {
            throw $exception;
        }

        if (null !== $errors) {
            return $this->createErrorResponse($errors);
        }

        if ($exception instanceof UniqueConstraintViolationException) {
            preg_match("/1062 Duplicate entry '(.+?)' for key/", $exception->getMessage(), $matches);

            if (isset($matches[1])) {
                $message = sprintf('Value %s already exists.', $matches[1]);
            } else {
                $message = 'Duplicate entry';
            }
            $errors = [new Error('data', null, null, 'model_unique', $message)];
        }  elseif ($exception instanceof ForeignKeyConstraintViolationException) {
            $errors = [new Error('data', null, null, 'model_integrity', sprintf('There is another resource using this resource.'))];
        }  elseif ($exception instanceof InvalidModelSet) {
            $errors = [new Error('data.'.$exception->getAttribute(), null, null, $exception->getType(), $exception->getMessage())];
        }  elseif ($exception instanceof InvalidRequest) {
            $errors = $exception->getErrors();
        }  elseif ($exception instanceof ModelException) {
            $errors = [new Error('data', null, null, $exception->getType(), $exception->getMessage())];
        }  else {
           throw $exception;
        }

        return $this->createErrorResponse($errors);
    }

    /**
     * @param ErrorInterface[] $errors
     *
     * @return JsonApiResponse
     */
    private function createErrorResponse(array $errors)
    {
        return new JsonApiResponse(
            $this->getEncoder()->encodeErrors($errors),
            JsonApiResponse::HTTP_BAD_REQUEST,
            [],
            true
        );
    }

    /**
     * @param Request $httpRequest
     * @param int     $currentPage
     * @param int     $totalPages
     *
     * @return array
     */
    private function buildLinks(Request $httpRequest, $currentPage, $totalPages)
    {
        $querySeparatorPos = strpos($httpRequest->getUri(), '?');
        $baseUrl = false !== $querySeparatorPos ? substr($httpRequest->getUri(), 0, $querySeparatorPos) : $httpRequest->getUri();
        $query = $httpRequest->query->all();

        $links = [];
        $links['self'] = $this->createPageLink($baseUrl, $query, $currentPage);
        $links['first'] = $this->createPageLink($baseUrl, $query, 1);
        if ($currentPage > 1) {
            $links['prev'] = $this->createPageLink($baseUrl, $query, $currentPage-1);
        }
        $links['next'] = $this->createPageLink($baseUrl, $query, $currentPage+1);
        if (null !== $totalPages) {
            $links['last'] = $this->createPageLink($baseUrl, $query, $totalPages);
            if ($currentPage == $totalPages) {
                unset($links['next']);
            }
        }

        return $links;
    }

    /**
     * @param Request $request
     * @param int     $pathPartsCount
     */
    private function setupJsonApiBaseUrl(Request $request, $pathPartsCount)
    {
        $baseUrl = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();

        $baseUrlParts = explode('/', $baseUrl);
        for ($i=0; $i<$pathPartsCount; $i++) {
            array_pop($baseUrlParts);
        }

        $this->jsonApiBaseUrl = implode('/', $baseUrlParts);
    }

    /**
     * @param string $baseUrl
     * @param array  $query
     * @param int    $page
     *
     * @return string
     */
    private function createPageLink($baseUrl, array $query, $page)
    {
        $query['page']['number'] = $page;

        return $baseUrl.'?'.urldecode(http_build_query($query));
    }
}
