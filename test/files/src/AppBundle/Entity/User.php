<?php

/*
 * Copyright (c) 2017 trivago
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author Moein Akbarof <moein.akbarof@trivago.com>
 * @date 2017-09-10
 */

namespace AppBundle\Entity;

use AppBundle\Value\Email;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @var User[]
     */
    private $friends = [];

    /**
     * sqlite doesn't accept not null without default value.
     * @ORM\Column(type="string", nullable=false, options={"default"=""})
     */
    private $email;

    /**
     * @ORM\Column(type="boolean", options={"default"=false})
     */
    private $isAdmin = false;

    /**
     * @param string $name
     * @param Email $email
     * @param boolean $isAdmin
     * @return User
     */
    public static function create($name, Email $email, $isAdmin = false)
    {
        $user = new self();
        $user->setName($name);
        $user->email = $email;
        $user->isAdmin = $isAdmin;

        return $user;
    }

    /**
     * @return string
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return User[]
     */
    public function getFriends()
    {
        return $this->friends;
    }

    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @param array $friends
     */
    public function setFriends($friends)
    {
        $this->friends = $friends;
    }

}
