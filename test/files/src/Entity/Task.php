<?php

namespace App\Entity;

use App\Value\TaskStatuses;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/**
 * @ORM\Entity()
 */
class Task
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $description;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ownedTasks")
     */
    private $owner;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedTasks")
     */
    private $assignee;

    /**
     * @var int
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $status;

    public function __construct($name, User $owner)
    {
        $this->setName($name);
        $this->owner = $owner;
        $this->status = TaskStatuses::OPEN;
    }

    public static function create($name, User $owner)
    {
        return new Task($name, $owner);
    }
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return USer
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return User
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        Assert::minLength($name, 1, 'Name must be at least 1 character long');
        Assert::maxLength($name, 50, 'Name must not be more than 50 characters');
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        Assert::maxLength($description, 200, 'Description must not be more than 200 characters');
        $this->description = $description;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        TaskStatuses::validate($this->getStatus(), $status);
        $this->status = $status;
    }

    /**
     * @param User $assignee
     */
    public function setAssignee(User $assignee = null)
    {
        $this->assignee = $assignee;
    }
}