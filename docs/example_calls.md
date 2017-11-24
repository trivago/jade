Example calls
=======
To get list of people: `GET http://localhost/people`

To get a person with id 1: `GET http://localhost/people/1`

To create a person: `POST http://localhost/people` with the following body:
```yaml
{
	"data": {
		"type": "people",
		"attributes": {
			"email": "test@test.com",
			"firstName": "Moein",
			"lastName": "Akbarof"
		},
		"relationships": {
			"company": {
				"data": {
					"id": 2,
					"type": "companies"
				}
			}
		}
	}
}
```
To update a person: `PATCH http://localhost/people/3` with the same body as create`

One point to keep in account for the example patch. As we mentioned the company does not have a setter which means you will get an error like this:
```yaml
{
    "errors": [
        {
            "id": "model",
            "code": "invalid_set",
            "title": "Relationship \"company\" can not be set as it has no setter."
        }
    ]
}
```

To get a list of people with filtering and sorting and include: `GET http://localhost/assets?include=company.country&sort=firstName&filter[0][type]=neq&filter[0][path]=firstName&filter[0][value]=Moein`
