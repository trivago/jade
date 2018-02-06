<?php

class UserApiTester extends ApiTester
{
    protected function getResourceName()
    {
        return 'users';
    }

    public function createInvalidUser()
    {
        $this->wantTo('Create a user with missing email');
        $this->client->wantToDoFailPost(
            'users',
            [],
            \JsonApi\RequestBuilder::create('users', ['name' => 'moein']),
            'Missing mandatory parameter \"email\"'
        );
    }
}
