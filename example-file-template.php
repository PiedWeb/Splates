<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use PiedWeb\Splates\Engine;

// Create engine - auto-detects project root from composer.json
$engine = new Engine();

// Example 1: Render a simple file template
echo "=== Example 1: Simple File Template ===\n";
echo $engine->render('tests/Template/fixtures/simple.php');
echo "\n";

// Example 2: Render with data
echo "=== Example 2: File Template with Data ===\n";
echo $engine->render('tests/Template/fixtures/greeting.php', ['name' => 'John']);
echo "\n";

// Example 3: Render with HTML escaping
echo "=== Example 3: File Template with Escaping ===\n";
echo $engine->render('tests/Template/fixtures/greeting.php', [
    'name' => '<script>alert("XSS")</script>',
]);
echo "\n";

// Example 4: Render from subdirectory
echo "=== Example 4: File Template from Subdirectory ===\n";
echo $engine->render('tests/Template/fixtures/partials/header.php', [
    'title' => 'My Page Title',
]);
echo "\n";

// Example 5: Explicit template directory
echo "=== Example 5: Custom Template Directory ===\n";
$customEngine = new Engine(templateDir: __DIR__ . '/tests');
echo $customEngine->render('Template/fixtures/greeting.php', ['name' => 'Alice']);
echo "\n";
