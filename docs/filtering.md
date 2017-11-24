Filtering
=======
You can pass a list of filters through the query parameter `filter`:

Jade supports both an array of filters in http way or json

The filters on the root level are joined together with an and.
It's kind of like saying they all are in a composite and filter.

The list can be validated like this:
filter: CompositeFilter
Filter: ExpressionFilter|CompositeFilter
CompositeFilter: {type: COMPOSITE_FILTER_TYPE, filters: Filter[]}
ExpressionFilter: {type: EXPRESSION_FILTER_TYPE, path: PATH, value: VALUE}

PATH: Any valid path. It can point to a field of a relationship as well. Example: owner.friends.name
Value: Any value

COMPOSITE_FILTER_TYPE can be any of these values:
* AND_EXPRESSION: and
* OR_EXPRESSION: or

EXPRESSION_FILTER_TYPE can be any of these values:
* EQUAL_TO: eq
* NOT_EQUAL_TO: neq
* GREATER_THAN: gt
* GREATER_THAN_EQUAL: gte
* LESS_THAN; lt
* LESS_THAN_EQUAL: lte
* CONTAINS: c
* NOT_CONTAINS: nc
* IN: in
* NOT_IN: nin

Each filter will be translated to a comparison expression in sql
* EQUAL_TO: 
  * If the value is null: ${PATH} IS NULL
  * If the value is not null: ${PATH} = "${VALUE}"
* NOT_EQUAL_TO: 
  * If the value is null: ${PATH} IS NOT NULL
  * If the value is not null: ${PATH} != "${VALUE}" 
* GREATER_THAN: ${PATH} > ${VALUE}
* GREATER_THAN_EQUAL: ${PATH} >= ${VALUE}
* LESS_THAN; ${PATH} < ${VALUE}
* LESS_THAN_EQUAL: ${PATH} <= ${VALUE}
* CONTAINS: ${PATH} LIKE "%${VALUE}%"
* NOT_CONTAINS: ${PATH} NOT LIKE "%${PATH}%"
* IN: ${PATH} IN (${VALUE})   => ${VALUE} must be an array
* NOT_IN: ${PATH} NOT IN (${VALUE}) => ${VALUE} must be an array

~~A simple example with http query:~~
Http query is not supported anymore.

Same query in json:
`/users?filter=[{"type":"neq","path":"firstName","value":"Moein"}]`

For a more advanced filtering:
To search for any user that is active and either his role is admin or his name is Moein
```
/users?filter=[
    {
        "type": "eq",
        "path": "isActive",
        "value": true
    },
    {
        "type": "neq",
        "path": "firstName",
        "value: null    
    }
    {
        "type": "or",
        "filters": [
            {
                "type": "eq",
                "path": "role",
                "value": "admin"
            },
            {
                "type": "eq",
                "path": "name",
                "value": "Moein"
            }
        ]
    }
]
```
The json value is formatted in a way to be easy to read.
You have to pass it to the api in a single line and escape it as well.
