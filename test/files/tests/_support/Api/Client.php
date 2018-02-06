<?php

namespace  Api;

use _generated\ApiTesterActions;
use _generated\UnitTesterActions;
use Codeception\Util\HttpCode;
use Codeception\Scenario;

class Client
{
    use ApiTesterActions;

    /** @var Scenario */
    private $scenario;

    private function __construct(Scenario $scenario)
    {
        $this->scenario = $scenario;
    }

    public function setAuthEmail($email)
    {
        $this->haveHttpHeader('X-EMAIL', $email);
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

    public function wantToDoSuccessfulPost($resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, null);
        $this->wantToDoRequest('POST', $path, $query, $body, $result, HttpCode::OK);
    }

    public function wantToDoSuccessfulPatch($id, $resourceName, array $query, array $body, $result)
    {
        $path = $this->getPath($resourceName, $id);
        $this->wantToDoRequest('PATCH', $path, $query, $body, $result, HttpCode::OK);
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
                $response = json_decode($this->grabResponse(), true);
                $resultAsArray = \json_decode(\json_encode($result->jsonSerialize()), true);
                $this->assertEmpty($this->arrayRecursiveDiff($resultAsArray, $response), 'Response did not match');
            } elseif (is_string($result)) {
                $this->seeResponseContains($result);
            }
        }
    }

    private function arrayRecursiveDiff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
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
