<?php

use Codeception\Scenario;
use Codeception\Util\HttpCode;
use JsonApi\Collection;
use JsonApi\Entity;
use JsonApi\Manager;
use JsonApi\MockedDataManager;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
abstract class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;

    /** @var Manager */
    protected $manager;

    /** @var \Api\Client */
    protected $client;

    /**
     * @param Scenario $scenario
     */
    public function __construct(Scenario $scenario)
    {
        $collection = Collection::create($this->getResourceName());
        $d = new MockedDataManager();
        $this->client = \Api\Client::create($scenario);
        $this->manager = Manager::create($this->client, $d, $collection);
        parent::__construct($scenario);
    }

    abstract protected function getResourceName();

    public function validateCurrentList()
    {
        $this->manager->validateList();
    }

    public function create($id, $key)
    {
        $this->wantTo(sprintf('Create a %s with key %s', $this->getResourceName(), $key));
        $this->manager->createEntity($id, $key);
    }

    public function delete($id)
    {
        $this->wantTo(sprintf('Delete a %s with id %s', $this->getResourceName(), $id));
        $this->manager->deleteEntity($id);
    }

    public function checkEntityDoesNotExist($id)
    {
        $this->manager->checkEntityDoesNotExit($id);
    }

    public function expectToSeeWithFilter($entityKeys, array $filter)
    {
        $this->wantTo("Check {$this->getResourceName()} list.");
        $this->manager->expectToSeeWithFilter($entityKeys, $filter);
    }

    public function getCollection()
    {
        return $this->manager->getCollection();
    }
}
