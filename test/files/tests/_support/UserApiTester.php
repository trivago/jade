<?php

use Codeception\Scenario;

class UserApiTester extends ApiTester
{
    public function createUser($id, $userKey)
    {
        $this->manager->createEntity($id, $userKey);
    }

    public function deleteUser($id)
    {
        $this->manager->deleteEntity($id);
    }

    protected function getResourceName()
    {
        return 'users';
    }
}
