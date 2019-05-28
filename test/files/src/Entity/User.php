<?php

namespace App\Entity;

use App\Value\Email;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/**
 * @ORM\Entity
 */
class User implements UserInterface
{
    /**
     * @var int
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
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $isAdmin;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $email;

    /**
     * @var Task[]
     * @ORM\OneToMany(targetEntity="Task", mappedBy="owner")
     */
    private $ownedTasks;

    /**
     * @var Task[]
     * @ORM\OneToMany(targetEntity="Task", mappedBy="owner")
     */
    private $assignedTasks;

    private function __construct(Email $email, $name, $isAdmin)
    {
        $this->email = $email->getValue();
        $this->isAdmin = $isAdmin;
        $this->setName($name);
    }

    public static function create(Email $email, $name, $isAdmin)
    {
        return new User($email, $name, $isAdmin);
    }

    /**
     * @return int
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return Task[]
     */
    public function getOwnedTasks()
    {
        return $this->ownedTasks;
    }

    /**
     * @return Task[]
     */
    public function getAssignedTasks()
    {
        return $this->assignedTasks;
    }

    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        Assert::minLength($name,3, 'Name must be at least 3 characters long');
        Assert::maxLength($name, 50, 'Name must not be more than 50 characters long');
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getRoles()
    {
        $roles = ['ROLE_USER'];
        if ($this->email === 'system@system.com') {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function eraseCredentials()
    {

    }
}