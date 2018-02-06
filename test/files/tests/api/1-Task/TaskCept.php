<?php

/** @var \Codeception\Scenario $scenario */
$I = new TaskApiTester($scenario);
$I->actAs('moein@test.com');
$I->create(1, 'task1');
$I->create(2, 'task2');
$I->actAs('miguel@test.com');
$I->create(3, 'task1');
$I->create(4, 'task2');

$I->update(2, ['name' => 'Subject of task2', 'description' => 'With description']);
$I->update(4, ['name' => 'Subject of task4', 'description' => null]);
$I->update(1, ['name' => 'Subject of task4', 'description' => '']);
$I->validateCurrentList();