Loading the routes
=======
First of all you have to include the loader in your routing:
```yaml
json_api_routes:
    prefix: /
    resource: .
    type: json_api
```

For each of the 4 actions(get entity, get collection, update and create) a route is created. This means you can take over
the whole process from the beginning by rewriting the whole route.

For example for people resource the following routes are generated:

```
json_api_get_people_collection                    GET      ANY      ANY    /people                        
json_api_get_people_single                        GET      ANY      ANY    /people/{id}                   
json_api_create_people                            POST     ANY      ANY    /people                        
json_api_update_people                            PATCH    ANY      ANY    /people/{id}           
json_api_delete_people                            DELETE   ANY      ANY    /people/{id}           
```
