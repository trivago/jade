<?php

namespace JsonApi;

class RequestBuilder
{
    /**
     * @param string $type
     * @param array $attributes
     * @param Pointer[] $relationships
     * @return array
     */
    public static function create($type, array $attributes = [], array $relationships = [])
    {
        return [
            'data' => [
                'type' => $type,
                'attributes' => $attributes,
                'relationships' => $relationships
            ]
        ];
    }
}
