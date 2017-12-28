<?php

use JsonApi\RequestBuilder;
/** @var \Codeception\Scenario $scenario */
$I = new UserApiTester($scenario);

// Validate empty list
$I->validateCurrentList();

//Simple failures
$I->checkEntityDoesNotExist(1);

//Parameter based failure
$I->createInvalidUser();

//Creating some data
$I->create(1, 'moein');
$I->create(2, 'miguel');
$I->create(3, 'albert');
$I->create(4, 'jose');
$I->create(5, 'martin');
$I->validateCurrentList();

$I->delete('albert');
$I->checkEntityDoesNotExist(3);
$I->create(6, 'javier');
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
