Quick start example
=============

Getting started
-------------

First install the library:

`composer require trivago/jade`

And add the bundle to the kernel:

```
public function registerBundles()
{
        $bundles = [
            ...
            new Trivago\Jade\Application\JadeBundle\TrivagoJadeBundle(),
            ...
        ];
}
```

Configuration
------------

In app/config/config.yml

```yaml
trivago_jade:
    manipulate:
        default_manipulate_role: ROLE_ADMIN
        default_read_role: ROLE_USER
    read:
        max_relationship_depth: 2 
    resources:
        -
            name: users
            entity_class: AppBundle\Entity\User
            allowed_actions: [create, update, delete]
            relationships:
                -
                    name: friends
                    type: users
        
```

Entities
-------

```php
<?php

namespace AppBundle\Entity;

class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var User[]
     */
    private $friends = [];

    /**
     * @param string $name
     * @return User
     */
    public static function create($name)
    {
        $user = new self();
        $user->setName($name);

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
     * @param array $friends
     */
    public function setFriends($friends)
    {
        $this->friends = $friends;
    }

}
```

Loading the routes
-------
in app/config/routing.yml
```yaml
json_api_routes:
    prefix: /api
    resource: .
    type: json_api
```

Now call GET /api/users to get the list of users

To create one call POST /api/users with this body
```json
{
    "data": {
		"type": "users",
		"attributes": {
			"name": "Moein"
		}
    }
}
``` 


Or update the user with PATCH /api/users
```json
{
    "data": {
        "id": 1,
		"type": "users",
		"attributes": {
			"name": "Moein"
		},
		"relationships": {
		    "friends": {
		        "data": [
                    {"id": 1, "type": "users"}
		        ]
		    }
		}
    }
}
``` 

Or delete the user with DELETE /api/users/1
