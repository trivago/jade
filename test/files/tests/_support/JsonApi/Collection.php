<?php

namespace JsonApi;

class Collection implements \JsonSerializable
{
    public static $fetchTotalCount = true;

    /** @var Entity[] */
    private $entities = [];

    /** @var string */
    private $resourceName;

    /** @var Meta */
    private $meta;

    /** @var array */
    private $filter = [];

    private function __construct(){}

    public static function create($resourceName)
    {
        $collection = new self();
        $collection->resourceName = $resourceName;
        $collection->meta = Meta::create($resourceName);

        return $collection;
    }

    public static function enableTotalCount()
    {
        self::$fetchTotalCount = true;
    }

    public static function disableTotalCount()
    {
        self::$fetchTotalCount = false;
    }

    public function addEntity(Entity $entity)
    {
        $entity->setInCollection();
        $this->entities[] = $entity;
        $this->meta->entityAdded();

        return $this;
    }

    public function setEntities(array $entities)
    {
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }

        return $this;
    }

    public function replaceEntity(Entity $entity)
    {
        foreach ($this->entities as $key => $collectionEntity) {
            if ($collectionEntity->isSame($entity)) {
                $entity->setInCollection();
                $this->entities[$key] = $entity;
                return;
            }
        }

        throw new \InvalidArgumentException('No entity found with id '.$entity->getId());
    }

    public function removeEntity($id)
    {
        foreach ($this->entities as $key => $entity) {
            if ($entity->isId($id)) {
                unset($this->entities[$key]);
                $this->entities = array_values($this->entities);
                $this->meta->entityRemoved();
                return;
            }
        }

        throw new \LogicException(sprintf('No entity found with id %s in collection with type %s', $id, $this->resourceName));
    }

    /**
     * @param $id
     * @return Entity
     */
    public function getEntity($id)
    {
        foreach ($this->entities as $entity) {
            if ($entity->isId($id)) {
                return $entity;
            }
        }

        throw new \LogicException(sprintf('No entity found with id %s in collection with type %s', $id, $this->resourceName));
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * @param array $filter
     * @return self
     */
    public function setFilter(array $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $collectionLinks = CollectionLinks::create($this->resourceName, $this->meta, $this->filter);
        $data = [
            'data' => $this->entities,
            'meta' => $this->meta,
            'links' => [
                'self' => $collectionLinks->self(),
                'first' => $collectionLinks->first(),
            ]
        ];

        if (self::$fetchTotalCount) {
            $data['links']['last'] = $collectionLinks->last();
        }

        return $data;
    }
}
