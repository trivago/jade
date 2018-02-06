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
        foreach ($relationships as $relationshipName => $relationship) {
            if (!is_array($relationship[0])) {
                $relationships[$relationshipName] = [
                    'data' => new Pointer($relationship[1], $relationship[0])
                ];

                continue;
            }

            foreach ($relationship as $index => $values) {
                $relationship[$index] = new Pointer($values[1], $values[0]);
            }

            $relationships[$relationshipName] = $relationship;
        }
        return [
            'data' => [
                'type' => $type,
                'attributes' => $attributes,
                'relationships' => $relationships
            ]
        ];
    }

    /**
     * @param string $id
     * @param string $type
     * @param array $attributes
     * @param Pointer[] $relationships
     * @return array
     */
    public static function update($id, $type, array $attributes, array $relationships)
    {
        $result = self::create($type, $attributes, $relationships);

        $result['data']['id'] = $id;

        return $result;
    }
}
