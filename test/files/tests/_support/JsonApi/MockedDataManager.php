<?php

namespace JsonApi;

use Symfony\Component\Yaml\Yaml;

class MockedDataManager
{
    private $data;

    private $ids = [];

    public function __construct()
    {
        $this->data = Yaml::parse(file_get_contents(__DIR__.'/../../_data/entities.yml'));
    }

    public function setId($resourceName, $key, $id)
    {
        $this->ids[$resourceName][$key] = $id;
    }

    public function removeId($resourceName, $key)
    {
        unset($this->ids[$resourceName][$key]);
    }

    public function getId($resourceName, $key)
    {
        if (!isset($this->ids[$resourceName][$key])) {
            throw new \LogicException("No id set with resource {$resourceName} and key $key");
        }

        return $this->ids[$resourceName][$key];
    }

    public function get($resourceName, $key)
    {
        if (!isset($this->data[$resourceName][$key])) {
            throw new \LogicException(sprintf('No %s with key %s', $resourceName, $key));
        }

        return $this->data[$resourceName][$key];
    }

    public function getAll($resourceName)
    {
        if (!isset($this->data[$resourceName])) {
            throw new \LogicException('No resource called '.$resourceName);
        }

        return $this->data[$resourceName];
    }
}
