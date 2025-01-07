#!/usr/bin/env php
<?php

declare(strict_types=1);

use peels\acl\Acl;
use peels\acl\User;
use peels\validate\exceptions\ValidationFailed;

define('__ROOT__', realpath(__DIR__ . '/../../../../'));

require_once __ROOT__ . '/vendor/autoload.php';

/* send config into application */
$container = cli(include __ROOT__ . '/config/config.php');

$pdo = $container->pdo;

$pdo->query('TRUNCATE TABLE orange_users');
$pdo->query('TRUNCATE TABLE orange_permissions');
$pdo->query('TRUNCATE TABLE orange_roles');
$pdo->query('TRUNCATE TABLE orange_user_role');
$pdo->query('TRUNCATE TABLE orange_role_permission');

$acl = new Acl([], container()->pdo, container()->validate);

$user = new User([], $acl, container()->session);

try {
    // #1
    $user = $acl->createUser('dmyers', 'dmyers@email.com', 'password', ['is_active' => 1]);
} catch (ValidationFailed $e) {
    echo 'ValidationFailed: ' . $e->getErrorsAsHtml() . PHP_EOL;
} catch (Throwable $e) {
    echo 'Throwable: ' . $e->getMessage() . PHP_EOL;
}

// #2
$guest = $acl->createUser('guest', 'guest@example.com', 'password', ['is_active' => 1]);

// #1
$role = $acl->createRole('admin', 'Adminstrator');

// #2
$guestRole = $acl->createRole('guest', 'Guest');

echo 'p1' . PHP_EOL;
$p1 = $acl->createPermission('uri://open/file', 'Open File', 'File');

echo 'p2' . PHP_EOL;
$p2 = $acl->createPermission('uri://close/file', 'Close File', 'File');

$role->addPermission($p1);
$role->addPermission($p2);

$user->addRole($role);

echo 'p3' . PHP_EOL;
$p3 = $acl->createPermission('uri://delete/file', 'Delete File', 'File');

$role->addPermission($p3);

$user->email = 'donmyers@foobar.com';

$user->update();

echo 'has => ' . ($user->can('uri://open/file') ? 'true' : 'false') . PHP_EOL;
echo 'does not have => ' . ($user->can('uri://foo/bar') ? 'true' : 'false') . PHP_EOL;

echo 'Is Admin => ' . ($user->isAdmin() ? 'true' : 'false') . PHP_EOL;
echo 'Is Logged In => ' . ($user->loggedIn() ? 'true' : 'false') . PHP_EOL;

$userE = $user->load();

var_dump($userE->id);
var_dump($userE->username);
var_dump($userE->email);
