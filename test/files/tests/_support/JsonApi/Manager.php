<?php

namespace JsonApi;

use Api\Client;

class Manager
{
    /** @var Client */
    protected $client;

    /** @var MockedDataManager */
    protected $dataManager;

    /** @var Collection */
    protected $collection;

    private function __construct(){}

    /**
     * @param Client $client
     * @param MockedDataManager $dataManager
     * @param Collection $collection
     * @return Manager
     */
    public static function create(Client $client, MockedDataManager $dataManager, Collection $collection)
    {
        $manager = new self();
        $manager->client = $client;
        $manager->dataManager = $dataManager;
        $manager->collection = $collection;

        return $manager;
    }

    public function getResourceName()
    {
        return $this->getCollection()->getResourceName();
    }

    public function getEntityAttributes($key)
    {
        return $this->dataManager->get($this->getResourceName(), $key)['static_properties'];
    }

    public function createEntity($id, $key)
    {
        $entity = Entity::create($id, $this->getResourceName())->setAttributes($this->getEntityAttributes($key));
        $this->client->wantToDoSuccessfulPost(
            "Create an entity with type {$this->getResourceName()} and key $key",
            $this->getResourceName(),
            [],
            RequestBuilder::create($this->getResourceName(), $this->getEntityAttributes($key)),
            $entity
        );
        $this->collection->addEntity($entity);

        $this->validateEntity($id);

        $this->dataManager->setId($this->getResourceName(), $key, $id);

        return $entity;
    }

    public function validateEntity($id)
    {
        $entity = clone $this->collection->getEntity($id);
        $this->client->wantToDoSuccessfulGet($this->getResourceName(), $id, [], $entity);
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function getEntityWithKey($key)
    {
        $id = $this->dataManager->getId($this->getResourceName(), $key);

        return $this->collection->getEntity($id);
    }

    public function validateList()
    {
        $this->client->wantToDoSuccessfulGet(
            $this->getResourceName(),
            null,
            [],
            $this->collection
        );
    }

    public function deleteEntity($key)
    {
        $id = $this->dataManager->getId($this->getResourceName(), $key);
        $this->client->wantToDoSuccessfulDelete(
            $this->getResourceName(),
            $id
        );
        $this->collection->removeEntity($id);
        $this->dataManager->removeId($this->getResourceName(), $key);
    }

    public function expectToSeeWithFilter($entityKeys, array $filter)
    {
        $collection = Collection::create($this->getResourceName())
            ->setFilter($filter);
        foreach ($entityKeys as $entityKey) {
            $collection->addEntity($this->getEntityWithKey($entityKey));
        }

        $this->client->wantToDoSuccessfulGet(
            $this->getResourceName(),
            null,
            ['filter' => json_encode($collection->getFilter())],
            $collection
        );
    }

    public function checkEntityDoesNotExit($id)
    {
        $this->client->wantToDoNotFoundRequest(sprintf('/%s/%s', $this->getResourceName(), $id), 'get');
        $this->client->wantToDoNotFoundRequest(sprintf('/%s/%s', $this->getResourceName(), $id), 'delete');
    }
}
