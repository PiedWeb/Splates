#!/usr/bin/env php
<?php

/**
 * Post-processor to fix short echo tags after Rector migration.
 *
 * Rector's printer converts short echo tags to multi-line PHP blocks.
 * This script converts them back to short echo tags.
 *
 * Usage:
 *     php bin/fix-short-echo-tags.php path/to/templates
 */

declare(strict_types=1);

if ($argc < 2) {
    echo "Usage: php bin/fix-short-echo-tags.php <path>\n";
    exit(1);
}

$path = $argv[1];

if (!file_exists($path)) {
    echo "Error: Path does not exist: {$path}\n";
    exit(1);
}

$files = is_dir($path)
    ? new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    )
    : [new SplFileInfo($path)];

$fixedCount = 0;

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    $original = $content;

    // Pattern: Convert multi-line php echo blocks back to short echo tags
    // Matches: <?php\n    echo expr;\n    ?> or <?php echo expr; ?>
    $pattern = '/<\?php\s+echo\s+(.+?);\s*\?>/s';
    $content = preg_replace_callback(
        $pattern,
        static function (array $matches): string {
            $expr = trim($matches[1]);
            // Use short echo syntax without semicolon
            return '<' . '?= ' . $expr . ' ?' . '>';
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($file->getPathname(), $content);
        echo "Fixed: {$file->getPathname()}\n";
        $fixedCount++;
    }
}

echo "\nFixed {$fixedCount} file(s).\n";
