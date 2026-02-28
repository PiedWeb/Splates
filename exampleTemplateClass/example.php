<?php

include '../vendor/autoload.php';

use PiedWeb\Splates\Engine;
use Templates\AppService;
use Templates\Profile;
use Templates\User;
use Templates\UserList;

// Create engine with global service
$engine = new Engine();
$engine->addGlobal('app', new AppService(
    appName: 'Splates Demo',
    appVersion: '4.0.0',
));

// Sample users
$users = [
    new User(
        id: 1,
        name: 'Alice Johnson',
        email: 'alice@example.com',
        role: 'admin',
        createdAt: new DateTimeImmutable('2024-01-15'),
    ),
    new User(
        id: 2,
        name: 'Bob Smith',
        email: 'bob@example.com',
        role: 'user',
        createdAt: new DateTimeImmutable('2024-03-22'),
    ),
    new User(
        id: 3,
        name: 'Charlie Brown',
        email: 'charlie@example.com',
        role: 'editor',
        createdAt: new DateTimeImmutable('2024-06-10'),
    ),
];

echo "=== Profile Page ===\n\n";

// Render a user profile with full IDE autocompletion
echo $engine->render(new Profile(
    user: $users[0],
));

echo "\n\n=== User List Page ===\n\n";

// Render user list
echo $engine->render(new UserList(
    users: $users,
    title: 'Team Members',
));
