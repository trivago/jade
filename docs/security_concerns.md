The security in jade is taken really seriously but still there are some points that you have to take in account.

Even if you setup the read access for a resource to ROLE_ADMIN it still can be accessed through a relationship.

For example in the following config:
```
trivago_jade:
    security:
        enabled: true
        default_manipulate_role: ROLE_ADMIN
        default_read_role: ROLE_ADMIN
    read: ~
    manipulate: ~
    resources:
        -
            name: actors
            entity_class: AppBundle\Entity\Actor
            roles:
                read: IS_AUTHENTICATED_ANONYMOUSLY
            relationships:
                -
                    name: creator
                    type: users
        -
            name: users
            entity_class: AppBundle\Entity\User
```
Access /users for a non admin user will give 403 error.
But if you access /actors?include=creator where actor.creator is a user then the data of the creator will be exposed.
