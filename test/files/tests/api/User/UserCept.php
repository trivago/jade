<?php

use JsonApi\RequestBuilder;
/** @var \Codeception\Scenario $scenario */
$I = new UserApiTester($scenario);

// Validate empty list
$I->validateCurrentList();

//Simple failures
$I->wantToCheckMethodIsNotAllowed('/users', 'delete');
$I->wantToCheckMethodIsNotAllowed('/users', 'patch');
$I->wantToCheckMethodIsNotAllowed('/users/1', 'post');
$I->wantToDoNotFoundRequest('/users/1', 'delete');
$I->wantToDoNotFoundRequest('/users/1', 'get');
$I->wantToDoNotFoundRequest('/users/1', 'delete');
$I->wantToDoNotFoundRequest('/users/1', 'patch');

//Parameter based failure
$I->wantToDoFailPost(
    'Create a user with missing email',
    'users',
    [],
    RequestBuilder::create('users', ['name' => 'moein']),
    'Missing mandatory parameter \"email\"'
);

//Creating some data
$I->createUser(1, 'moein');
$I->createUser(2, 'miguel');
$I->createUser(3, 'albert');
$I->createUser(4, 'jose');
$I->createUser(5, 'martin');
$I->validateCurrentList();

$I->deleteUser('albert');
$I->wantToDoNotFoundRequest('/users/3', 'get');
$I->wantToDoNotFoundRequest('/users/3', 'delete');
$I->createUser(6, 'javier');
$I->validateCurrentList();

//Simple filtering
$I->expectToSeeWithFilter(['moein', 'miguel'], [
    [
        'type' => 'eq',
        'path' => 'isAdmin',
        'value' => true
    ]
]);

$I->expectToSeeWithFilter(['jose', 'martin', 'javier'], [
    [
        'type' => 'eq',
        'path' => 'isAdmin',
        'value' => false
    ]
]);

//Advanced filtering
$I->expectToSeeWithFilter(['moein', 'javier'], [
    [
        'type' => 'or',
        'filters' => [
            [
                'type' => 'and',
                'filters' => [
                    [
                        'type' => 'eq',
                        'path' => 'isAdmin',
                        'value' => true,
                    ],
                    [
                        'type' => 'c',
                        'path' => 'name',
                        'value' => 'n',
                    ],
                ],
            ],
            [
                'type' => 'eq',
                'path' => 'name',
                'value' => 'Javier',
            ]
        ]
    ]
]);
