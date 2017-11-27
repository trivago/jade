<?php

namespace JsonApi;

class LinkBuilder
{
    const BASE_URL = 'http://localhost';

    public static function selfEntity($type, $id)
    {
        return self::BASE_URL.'/'.$type.'/'.$id;
    }
}
