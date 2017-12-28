<?php

namespace  Api;

use Codeception\Util\HttpCode;
use Codeception\Scenario;
use Symfony\Component\HttpFoundation\JsonResponse;

class Client
{
    use \_generated\ApiTesterActions;

    /** @var Scenario */
    private $scenario;

    private function __construct(Scenario $scenario)
    {
        $this->scenario = $scenario;
    }

    public static function create(Scenario $scenario)
    {
        $client = new self($scenario);

        return $client;
    }

    protected function getScenario()
    {
        return $this->scenario;
    }

    public function wantToDoSuccessfulGet($resourceName, $id, array $query, $result)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest('GET', $path, $query, [], $result, HttpCode::OK);
    }

    public function wantToDoFailGet($resourceName, $id, array $query, $result)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest('GET', $path, $query, [], $result, HttpCode::BAD_REQUEST);
    }

    public function wantToDoSuccessfulPost($message, $resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, null);
        $this->wantToDoRequest('POST', $path, $query, $body, $result, HttpCode::OK);
    }

    public function wantToDoFailPost($resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, null);
        $this->wantToDoRequest('POST', $path, $query, $body, $result, HttpCode::BAD_REQUEST);
    }

    public function wantToDoSuccessfulDelete($resourceName, $id)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest('DELETE', $path, [], [], '', HttpCode::NO_CONTENT);
    }

    public function wantToDoNotFoundRequest($path, $method)
    {
        $method = 'send'.strtoupper($method);
        $this->$method($path);
        $this->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function wantToCheckMethodIsNotAllowed($path, $method)
    {
        $method = 'send'.strtoupper($method);
        $this->$method($path);
        $this->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
    }

    private function wantToDoRequest($method, $path, array $query, array $body, $result, $code)
    {
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
