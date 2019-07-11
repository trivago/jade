<?php

namespace App\Listener;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Trivago\Jade\Application\JsonApi\Request\CollectionRequest;
use Trivago\Jade\Application\JsonApi\Request\CreateRequest;
use Trivago\Jade\Application\JsonApi\Request\DeleteRequest;
use Trivago\Jade\Application\JsonApi\Request\EntityRequest;
use Trivago\Jade\Application\JsonApi\Request\UpdateRequest;
use Trivago\Jade\Application\Listener\DeleteListener;
use Trivago\Jade\Application\Listener\RequestListener;
use Trivago\Jade\Domain\ResourceManager\Bag\ResourceRelationshipBag;
use Trivago\Jade\Domain\ResourceManager\Bag\ToOneRelationship;

class OwnerListener implements RequestListener, DeleteListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * OwnerListener constructor.
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * @inheritdoc
     */
    public function onGetEntityRequest(EntityRequest $request)
    {

    }

    /**
     * @inheritdoc
     */
    public function onGetCollectionRequest(CollectionRequest $request)
    {

    }

    /**
     * @inheritdoc
     */
    public function onCreateRequest(CreateRequest $request)
    {
        $relationships = $request->getRelationships()->getAll();
        foreach ($relationships as $name => $relationship) {
            if ($name == 'owner') {
                throw new \InvalidArgumentException('You can not set the owner');
            }
        }

        $relationships['owner'] = new ToOneRelationship('users', User::class, $this->tokenStorage->getToken()->getUser()->getId());

        return new CreateRequest($request->getResourceName(), $request->getAttributes(), new ResourceRelationshipBag($relationships));
    }

    /**
     * @inheritdoc
     */
    public function onUpdateRequest(UpdateRequest $request)
    {
        // TODO: Implement onUpdateRequest() method.
    }

    /**
     * @inheritdoc
     */
    public function onDeleteRequest(DeleteRequest $request)
    {
        return;
    }

    /**
     * @param Task $task
     */
    public function beforeDelete($task)
    {
        if ($this->tokenStorage->getToken()->getUser()->getId() !== $task->getOwner()->getId()) {
            throw new \InvalidArgumentException('Only the owner can delete a task');
        }
    }

    /**
     * @inheritdoc
     */
    public function afterDelete($entity)
    {
        return;
    }


    /**
     * @inheritdoc
     */
    public function supports($resourceName)
    {
        return $resourceName === 'tasks';
    }
}