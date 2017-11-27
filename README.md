Jade(Json Api Doctrine Exposer)
=============

What is it?
-------------
Jade is a library created in an effort to create a simple way to create Json API server using doctrine entities.
It supports all the CRUD functionality like filtering, sorting and including relationships.
You just define the entities then using the configuration expose different routes.

It is done in a way to be easy to customize any part you need.

Another library?
-------------
Before starting developing this library we went through other available libraries even thinking about contributing to them.
But the issue was that customizing those libraries to our needs was complicated and sometimes needed too much effort to even set it up.
In case of Jade in 5 minutes you can have a fully functional API running.

Getting started
-------------
You can find a quick start example in docs/example.md

First install the library:

`composer require trivago/jade`

Next add the bundle to the kernel:

```php
public function registerBundles()
{
        $bundles = [
            ...
            new Trivago\Jade\Application\Framework\JadeBundle\TrivagoJadeBundle(),
            ...
        ];
}
```

And then add the routing
```yaml
json_api_routes:
    prefix: /api
    resource: .
    type: json_api

```

And then you have to setup the configuration.

Configuration
------------
[Read it here](docs/configuration.md)

Example configuration
------------
[Read it here](docs/example_configuration.md)

Entities
-------
[Read it here](docs/entities.md)

Loading the routes
-------
[Read it here](docs/routing.md)

Filtering
---------
[Read it here](docs/filtering.md)

Sorting
-------
[Read it here](docs/sorting.md)

Including
---------
[Read it here](docs/including.md)

Listeners
---------
[Read it here](docs/listeners.md)

Example calls
-------------
[Read it here](docs/example_calls.md)

Security concerns
-------------
[Read it here](docs/security_concerns.md)

Tests
-------------
[Read it here](docs/tests.md)

Missing features
----------------
* Allow choosing which fields to be included.
* Validate the request for extra keys that are not valid.
* Create the relationship urls.
* Contain for a path that is string works fine. Find a new filter type name for path being an array that contains value.
* Use ResourceMapper to avoid filtering or sorting on columns that are not rendered [security]
