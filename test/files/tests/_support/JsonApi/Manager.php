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

    public function getWritableEntityAttributes($key)
    {
        return $this->dataManager->get($this->getResourceName(), $key)['writable_attributes'];
    }

    public function getAllEntityAttributes($key)
    {
        $entity = $this->dataManager->get($this->getResourceName(), $key);
        return array_merge(
            $entity['writable_attributes'],
            isset($entity['readonly_attributes']) ? $entity['readonly_attributes'] : []
        );
    }

    public function getEntityWritableRelationships($key)
    {
        $entity = $this->dataManager->get($this->getResourceName(), $key);

        return isset($entity['writable_relationships']) ? $entity['writable_relationships'] : [];
    }

    public function createEntity($id, $key)
    {
        $entity = Entity::create($id, $this->getResourceName())->setAttributes($this->getAllEntityAttributes($key));
        $this->client->wantToDoSuccessfulPost(
            $this->getResourceName(),
            [],
            RequestBuilder::create($this->getResourceName(), $this->getWritableEntityAttributes($key), $this->getEntityWritableRelationships($key)),
            $entity
        );
        $this->collection->addEntity($entity);

        $this->validateEntity($id);

        $this->dataManager->setId($this->getResourceName(), $key, $id);

        return $entity;
    }

    public function updateEntity($id, $attributes, $relationships)
    {
        $entity = clone $this->collection->getEntity($id);
        foreach ($attributes as $name => $relationship) {
            $entity->setAttribute($name, $relationship);
        }

        $formattedRelationships = [];

        foreach ($relationships as $name => $relationship) {
            if (is_array($relationship[0])) {
                $formattedRelationships[$name] = array_map(function($rel){return new Pointer($rel[1], $rel[0]);},$relationship);
                $entity->setCollectionRelationship($name, $formattedRelationships[$name]);
            } else {
                $formattedRelationships[$name] = new Pointer($relationship[1], $relationship[0]);
                $entity->setSingleRelationship($name, $formattedRelationships[$name]);
            }
        }

        $this->client->wantToDoSuccessfulPatch(
            $id,
            $this->getResourceName(),
            [],
            RequestBuilder::update($id, $this->getResourceName(), $attributes, $relationships),
            $entity
        );

        $this->collection->replaceEntity($entity);
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
