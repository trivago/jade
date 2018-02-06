<?php

class TaskApiTester extends ApiTester
{
    protected function getResourceName()
    {
        return 'tasks';
    }

    public function createInvalidTask()
    {
        $this->wantTo('Create a task with missing name');
        $this->client->wantToDoFailPost(
            $this->getResourceName(),
            [],
            \JsonApi\RequestBuilder::create($this->getResourceName(), ['description' => 'Just a description']),
            'Missing mandatory parameter \"name\"'
        );
    }
}
