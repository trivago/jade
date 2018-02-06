<?php

namespace JsonApi;

class Entity implements \JsonSerializable
{
    /** @var string */
    private $id;
    /** @var  string */
    private $type;
    /** @var array */
    private $attributes = [];
    /** @var Pointer[] */
    private $singleRelationships = [];
    /** @var Pointer[][] */
    private $collectionRelationships = [];
    /** @var bool */
    private $isInCollection = false;

    private function __construct(){}

    public function setInCollection()
    {
        $this->isInCollection = true;
    }

    /**
     * @param mixed $id
     * @return bool
     */
    public function isId($id)
    {
        return $this->id === (string) $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isSame(Entity $entity)
    {
        return $this->id === $entity->id;
    }

    /**
     * @param string $id
     * @param string $type
     * @return Entity
     */
    public static function create($id, $type)
    {
        $entity = new self();
        $entity->id = (string) $id;
        $entity->type = $type;

        return $entity;
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    public function setSingleRelationship($name, Pointer $pointer)
    {
        $this->singleRelationships[$name] = $pointer;

        return $this;
    }

    public function setSingleRelationships(array $singleRelationships)
    {
        foreach ($singleRelationships as $name => $pointer) {
            $this->setSingleRelationship($name, $pointer);
        }
    }

    public function setCollectionRelationship($name, $pointers)
    {
        $this->collectionRelationships[$name] = $pointers;
    }

    public function setCollectionRelationships(array $collectionRelationships)
    {
        foreach ($collectionRelationships as $name => $pointers) {
            $this->setCollectionRelationship($name, $pointers);
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $data = [
            'type' => $this->type,
            'id' => $this->id,
        ];

        if ($this->attributes) {
            $data['attributes'] = $this->attributes;
        }

        $relationships = array_merge($this->collectionRelationships, $this->singleRelationships);

        if ($relationships) {
            foreach ($relationships as $name => $relationship) {
                $data['relationships'][$name] = [
                    'data' => $relationship
                ];
            }
        }

        $data['links'] = ['self' => LinkBuilder::selfEntity($this->type, $this->id)];

        return $this->isInCollection ? $data : ['data' => $data];
    }

    public function __clone()
    {
        $this->isInCollection = false;
    }
}
