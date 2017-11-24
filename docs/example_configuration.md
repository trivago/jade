Example configuration
=======

This is a simple configuration containing all the possible keys.

```yaml
trivago_jade:
    debug: "%kernel.debug%"
    security:
        enabled: true
        default_manipulate_role: ROLE_ADMIN
        default_read_role: ROLE_USER
    read:
        max_per_page:         100
        default_per_page:     50
        fetch_total_count:    true
        max_relationship_depth: 2
    manipulate: 
        on_update_method: updated
        create_method: create
        include_relationships: false
    resources:
        -
            name: countries
            entity_class: AppBundle\Entity\Country
            allowed_actions: [create, update, delete]
            value_objects:
                name: AppBundle\Value\CountryName
            roles:
                create: ROLE_USER 
                update: ROLE_USER
                delete: ROLE_USER
                read:   ROLE_USER
            relationships:
                -
                    name: cities
                    type: cities
        -
            name: cities
            entity_class: AppBundle\Entity\City
            repository_service_id: spotbox.city_repository
            manager_service_id: spotbox.city_manager
            relationships:
                -
                    name: country
                    type: countries
        -
            name: people
            entity_class: AppBundle\Entity\Person
            allow_create: true
            allow_update: true
            # In this example the privatePhone attribute is exposed if (the user has ROLE_ADMIN and $resource->isNotDeleted($user) returns true) OR $resource->isSame($user) returns true
            # $resource is the object to be serialized and $user is the authenticated user calling the api. If no user is authenticated the field is not shown at all
            attributes_permissions:
                privatePhone:
                    - [[byRole, ROLE_ADMIN], [byMethod, isNotDeleted]]
                    - [[byMethod, isSame]]
            virtual_paths: # Virtual paths are used for easier filtering. So instead of company.name you can use directly companyName in the filter path
                companyName: company.name
            virtual_properties: # Since fullName is not attribute of the class but we want it to appear in the attributes we define it as a virtual property
                fullName: getFullName
            excluded_attributes:
                - gender
            parent: assets
            relationships:
                -
                    name: company
                    type: companies
        -
            name: companies
            entity_class: AppBundle\Entity\Company
            allow_create: true
            attributes_permissions:
                name:
                    - [] # Since there is no rule on this attribute but it appears here only anonymous users can't see this attribute
            allow_update: true
            parent: assets
        -
            name: assets
            entity_class: AppBundle\Entity\Asset
            relationships:
                -
                    name: locationPreferences
                    type: cities
```
