<?php

namespace JsonApi;

class Manager
{
    /** @var \ApiTester */
    protected $I;

    /** @var MockedDataManager */
    protected $dataManager;

    /** @var Collection */
    protected $collection;

    private function __construct(){}

    /**
     * @param \ApiTester $I
     * @param MockedDataManager $dataManager
     * @param Collection $collection
     * @return Manager
     */
    public static function create(\ApiTester $I, MockedDataManager $dataManager, Collection $collection)
    {
        $manager = new self();
        $manager->I = $I;
        $manager->dataManager = $dataManager;
        $manager->collection = $collection;

        return $manager;
    }

    public function getResourceName()
    {
        return $this->getCollection()->getResourceName();
    }

    public function getEntityData($key)
    {
        return $this->dataManager->get($this->getResourceName(), $key);
    }

    public function createEntity($id, $key)
    {
        $entity = Entity::create($id, $this->getResourceName())->setAttributes($this->getEntityData($key));
        $this->I->wantToDoSuccessfulPost(
            "Create an entity with type {$this->getResourceName()} and key $key",
            $this->getResourceName(),
            [],
            RequestBuilder::create($this->getResourceName(), $this->getEntityData($key)),
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
        $this->I->wantToDoSuccessfulGet("Validate entity {$this->getResourceName()} with id $id", $this->getResourceName(), $id, [], $entity);
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
        $this->I->wantToDoSuccessfulGet(
            "Validate {$this->getResourceName()} list",
            $this->getResourceName(),
            null,
            [],
            $this->collection
        );
    }

    public function deleteEntity($key)
    {
        $id = $this->dataManager->getId($this->getResourceName(), $key);
        $this->I->wantToDoSuccessfulDelete(
            "Remove from {$this->getResourceName()} id $id",
            $this->getResourceName(),
            $id
        );
        $this->collection->removeEntity($id);
        $this->dataManager->removeId($this->getResourceName(), $key);
    }
}
