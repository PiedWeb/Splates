<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use Override;
use InvalidArgumentException;
use RuntimeException;
use PiedWeb\Splates\Engine;

/**
 * File-based template that includes a PHP file from the template directory.
 */
class TemplateFile extends Template
{
    private readonly string $resolvedPath;

    public function __construct(
        Engine $engine,
        private readonly string $templatePath,
    ) {
        parent::__construct($engine);

        // Validate and resolve path
        $this->resolvedPath = $this->resolvePath($templatePath);
    }

    #[Override]
    protected function display(): void
    {
        // Create helper closures
        $e = fn (int|float|string|bool $value): string => $this->escape($value);

        // Merge helpers with template data
        $__vars = array_merge($this->data(), [
            'e' => $e,
        ]);

        // Include in isolated scope
        (static function (string $__path, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            include $__path;
        })($this->resolvedPath, $__vars);
    }

    #[Override]
    public function path(): string
    {
        return $this->templatePath;
    }

    #[Override]
    public function exists(): bool
    {
        return file_exists($this->resolvedPath);
    }

    /**
     * Resolve and validate template path.
     *
     * @throws InvalidArgumentException If path is invalid or outside allowed directory
     * @throws RuntimeException If file not found or directory cannot be resolved
     */
    private function resolvePath(string $path): string
    {
        // Validate path - block null bytes
        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException(
                'Invalid template path: contains null byte'
            );
        }

        // Resolve template directory once
        $templateDir = $this->engine->getTemplateDir();
        $realTemplateDir = realpath($templateDir);
        if ($realTemplateDir === false) {
            throw new RuntimeException(
                \sprintf('Template directory does not exist or is not accessible: "%s"', $templateDir)
            );
        }

        // Build and resolve full path
        $fullPath = $realTemplateDir . '/' . $path;
        $realPath = realpath($fullPath);
        if ($realPath === false) {
            throw new RuntimeException(
                \sprintf('Template file not found: "%s" (looked in "%s")', $path, $realTemplateDir)
            );
        }

        // Ensure resolved path is within template directory
        if (! str_starts_with($realPath, $realTemplateDir . \DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException(
                \sprintf('Template path "%s" resolves outside the allowed directory "%s"', $path, $realTemplateDir)
            );
        }

        return $realPath;
    }
}
