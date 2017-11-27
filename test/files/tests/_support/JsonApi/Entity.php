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
    private $relationships = [];
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

    public function setRelationship($name, Pointer $pointer)
    {
        $this->relationships[$name] = $pointer;

        return $this;
    }

    public function setRelationships(array $relationships)
    {
        foreach ($relationships as $name => $pointer) {
            $this->setRelationship($name, $pointer);
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

        if ($this->relationships) {
            $data['relationships'] = $this->relationships;
        }

        $data['links'] = ['self' => LinkBuilder::selfEntity($this->type, $this->id)];

        return $this->isInCollection ? $data : ['data' => $data];
    }

    public function __clone()
    {
        $this->isInCollection = false;
    }
}
