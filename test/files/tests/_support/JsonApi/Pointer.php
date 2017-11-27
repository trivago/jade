<?php

namespace JsonApi;

class Pointer implements \JsonSerializable
{
    /** @var string */
    private $id;
    /** @var string */
    private $type;

    /**
     * @param string $id
     * @param string $type
     */
    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
