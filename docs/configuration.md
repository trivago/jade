Configuration
=======

This is the configuration reference created by running `bin/console config:dump-reference TrivagoJadeBundle`.
```
# Default configuration for "TrivagoJadeBundle"
trivago_jade:

    # If you set this true the exceptions will be thrown instead of being converted to error response.
    debug:                false
    security:

        # If you do not use security in your project just set this value to false.
        enabled:              false

        # Default role for creating/updating any resource. This can be changed on each resource.
        default_manipulate_role: null

        # Default role for reading any resource. This can be changed on each resource.
        default_read_role:    null
    read:
        enabled:              true

        # The maximum number of items per page
        max_per_page:         100

        # The default number of items per page.
        default_per_page:     50

        # If enabled the repository will also fetches the total count of the results and it will be returned in the api.
        fetch_total_count:    true

        # Limits the consumer on how many level of include it can request. For example include=locationPreferences.country.cities will fail since it's 3 level of relationship
        max_relationship_depth: 2
    manipulate:
        enabled:              true

        # If set to true the relationships that are updated in the request will be returned in the response.
        include_relationships: false

        # The name of the function that is called on the resource when it's updated.
        # The resource does not need to have this method. Mostly used to update updatedAt field or validate the object state.
        on_update_method:     updated

        # The name of the static method needed on each entity that allows creation of the entity.
        create_method:        create
    resources:

        # Prototype
        -

            # This is the name that is also used in the url. So to fetch the list of countries you access /countries. You can use any alphanumeric character together with _ and -
            name:                 ~ # Required

            # The entity class for this resource.
            entity_class:         ~ # Required

            # A list of attributes that you want to exclude for reading. Take in account this is for reading.
            # If you want to exclude writing attributes just do not call your method setMyAttribute(the convention is editMyAttribute)
            excluded_attributes:  []

            # This is one of the complex configs of this library. You can decide who can see what based on either the role or the state of the object.
            # It's a collection of AND condition. It means that each item of this collection defines a list of AND condition and then the result of the items are evaluated together with OR.
            # Each condition itself is an array with 2 elements. First element can be either `byRole` or `byMethod`. In case of `byRole` the second value of condition is the role and in case of `byMethod` it is the method that will be called on the resource.
            # An example of an item of the collection: [[byRole, ROLE_ADMIN], [byMethod, isNotDeleted]]
            attributes_permissions:

                # Prototype
                -                     []

            # Virtual paths are used for easier filtering. So instead of company.name you can use directly companyName in the filter path.
            virtual_paths:        []

            # Virtual properties are a set of key values where the key is the name of the virtual property and the value the method used to fetch the value, The virtual property will appear in the attributes but is not filterable or sortable.
            virtual_properties:   []

            # The relationships to expose to the api.
            relationships:

                # Prototype
                -
                    name:                 ~ # Required
                    type:                 ~ # Required

            # The service id of the repository. The repository has to implement the interface ResourceRepository.
            # If not provided the default doctrine repository of this entity is used. The encapsulator class is DoctrineResourceRepository.
            repository_service_id: null

            # The id of the manager service. The manager is responsible of creating and updating the entity. It has to implement ResourceManager.
            # The default value for this config is trivago_jade.generic_resource_manager with class GenericResourceManager.
            manager_service_id:   trivago_jade.generic_resource_manager

            # With this attribute the resource will inherit the following values of the parent: relationships, value_objects, excluded_attributes, virtual_paths, attributes_permissions, virtual_properties
            parent:               null

            # If your setter or create method needs a value object instead of the plain value you can use this option
            value_objects:        []

            # The actions that are allowed on this entity. If nothing specified the entity can only be read.
            allowed_actions:      []

            # This will rewrite the default role mentioned above.
            roles:
                create:               null
                update:               null
                read:                 null
                delete:               null
```
