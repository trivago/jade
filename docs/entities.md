Entities
=======

The entities play a big role in this library. It's the entity who controls what can be exposed and what can be updated.
As the idea of this library is to practically expose the entity there is no separate configuration for exposing fields.
It also dictates which fields or relationships are mandatory for 

For example the following entity:
```php
<?php

namespace SpotBox\Asset\Entity;

use SpotBox\Core\Value\Gender\Genders;

class Person extends Asset
{
    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var int
     */
    private $gender;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param Company $company
     * @return Person
     */
    public static function create($firstName, $lastName, Company $company)
    {
        $person = new self();
        $person->setFirstName($firstName);
        $person->setLastName($lastName);
        $person->company = $company;

        return $person;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @param int $gender
     */
    public function setGender($gender)
    {
        Genders::validate($gender);
        $this->gender = $gender;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }
}
```

In this example the gender of the person is not exposed in the api but still can be set in a patch request.
As for the company it can only be passed when creating the object but it can't be updated later on because it does not have the setter.
