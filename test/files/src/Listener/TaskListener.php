<?php

namespace App\Listener;

use App\Entity\Task;
use App\Validator\BlacklistWordsValidator;
use Trivago\Jade\Application\Listener\CreateListener;
use Trivago\Jade\Application\Listener\UpdateListener;

class TaskListener implements UpdateListener, CreateListener
{
    /**
     * @var BlacklistWordsValidator
     */
    private $blacklistedWordsValidator;

    /**
     * TaskListener constructor.
     * @param BlacklistWordsValidator $blacklistedWordsValidator
     */
    public function __construct(BlacklistWordsValidator $blacklistedWordsValidator)
    {
        $this->blacklistedWordsValidator = $blacklistedWordsValidator;
    }

    /**
     * @var Task $task
     */
    public function beforeCreate($task)
    {
        $this->validateTask($task);
    }

    /**
     * @param object $entity
     */
    public function beforeSave($entity)
    {
        return;
    }

    /**
     * The object instance is created by and saved
     * @param object $entity
     * @deprecated Use afterSave instead
     */
    public function afterCreate($entity)
    {
        return;
    }

    /**
     * @param object $entity
     */
    public function afterSave($entity)
    {
        // TODO: Implement afterSave() method.
    }

    /**
     * @param Task $task
     */
    public function beforeUpdate($task)
    {
        $this->validateTask($task);
    }

    /**
     * @param object $entity
     */
    public function afterUpdate($entity)
    {
        return;
    }

    private function validateTask(Task $task)
    {
        $this->blacklistedWordsValidator->validateBlacklistedWords($task->getName());
        $this->blacklistedWordsValidator->validateBlacklistedWords($task->getDescription());
    }

    /**
     * @inheritdoc
     */
    public function supports($resourceName)
    {
        return $resourceName === 'tasks';
    }
}