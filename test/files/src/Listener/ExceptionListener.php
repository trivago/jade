<?php

namespace App\Listener;

use Trivago\Jade\Application\JsonApi\Error\Error;

class ExceptionListener implements \Trivago\Jade\Application\Listener\ExceptionListener
{

    /**
     * @inheritdoc
     */
    public function onException(\Exception $exception)
    {
        if ($exception instanceof \InvalidArgumentException) {
            return [
                new Error('domain',
                    null,
                    null,
                    $exception->getCode(),
                    $exception->getMessage()
                ),
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function supports($resourceName)
    {
        return true;
    }
}