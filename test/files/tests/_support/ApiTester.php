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

    /**
     * @param Scenario $scenario
     */
    public function __construct(Scenario $scenario)
    {
        $collection = Collection::create($this->getResourceName());
        $d = new MockedDataManager();
        $this->manager = Manager::create($this, $d, $collection);
        parent::__construct($scenario);
    }

    abstract protected function getResourceName();

    public function wantToDoSuccessfulGet($message, $resourceName, $id, array $query, $result)
    {
        $path = $this->getPath($resourceName, $id);
        if ($result instanceof Collection && count($result->getFilter())) {
            $query['filter'] = json_encode($result->getFilter());
        }
        $this->wantToDoRequest($message, 'GET', $path, $query, [], $result, HttpCode::OK);
    }

    public function wantToDoFailGet($message, $resourceName, $id, array $query, $result)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest($message, 'GET', $path, $query, [], $result, HttpCode::BAD_REQUEST);
    }

    public function wantToDoSuccessfulPost($message, $resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, null);
        $this->wantToDoRequest($message, 'POST', $path, $query, $body, $result, HttpCode::OK);
    }

    public function wantToDoFailPost($message, $resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, null);
        $this->wantToDoRequest($message, 'POST', $path, $query, $body, $result, HttpCode::BAD_REQUEST);
    }

    public function wantToDoSuccessfulDelete($message, $resourceName, $id)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest($message, 'DELETE', $path, [], [], '', HttpCode::NO_CONTENT);
    }

    public function wantToDoNotFoundRequest($path, $method)
    {
        $this->wantTo('Call a non existing url');
        $method = 'send'.strtoupper($method);
        $this->$method($path);
        $this->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function wantToCheckMethodIsNotAllowed($path, $method)
    {
        $this->wantTo('Call a url with a method that is not allowed');
        $method = 'send'.strtoupper($method);
        $this->$method($path);
        $this->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
    }

    public function validateCurrentList()
    {
        $this->manager->validateList();
    }

    public function expectToSeeWithFilter($entityKeys, array $filter)
    {
        $collection = Collection::create($this->getResourceName())
            ->setFilter($filter);
        foreach ($entityKeys as $entityKey) {
            $collection->addEntity($this->manager->getEntityWithKey($entityKey));
        }

        $this->wantToDoSuccessfulGet(
            "Check {$this->getResourceName()} list.",
            $this->getResourceName(),
            null,
            [],
            $collection
        );
    }

    public function getCollection()
    {
        return $this->manager->getCollection();
    }

    private function wantToDoRequest($message, $method, $path, array $query, array $body, $result, $code)
    {
        $this->wantTo($message);
        $path .= '?'.http_build_query($query);
        $method = 'send'.strtoupper($method);
        if ($method === 'sendGET') {
            $this->sendGET($path);
        } else {
            $this->$method($path, \json_encode($body));
        }
        $this->seeResponseCodeIs($code);
        if ($code !== HttpCode::NO_CONTENT) {
            $this->seeResponseIsJson();
            if ($result instanceof \JsonSerializable) {
                $this->seeResponseEquals(\json_encode($result, JsonResponse::DEFAULT_ENCODING_OPTIONS));
            } elseif (is_string($result)) {
                $this->seeResponseContains($result);
            }
        }
    }

    private function getPath($resourceName, $id)
    {
        $path = '/'.$resourceName;
        if ($id) {
            $path .= '/'.$id;
        }

        return $path;
    }
}
