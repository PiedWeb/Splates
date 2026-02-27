<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

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

    public function path(): string
    {
        return $this->templatePath;
    }

    public function exists(): bool
    {
        return file_exists($this->resolvedPath);
    }

    /**
     * Resolve and validate template path.
     *
     * @throws \InvalidArgumentException If path is invalid
     * @throws \RuntimeException If file not found
     */
    private function resolvePath(string $path): string
    {
        // Validate path - block null bytes
        if (str_contains($path, "\0")) {
            throw new \InvalidArgumentException(
                'Invalid template path: contains null byte'
            );
        }

        // Get template directory from Engine
        $templateDir = $this->engine->getTemplateDir();

        // Build full path
        $fullPath = $templateDir . '/' . $path;

        // Resolve and validate
        $realPath = realpath($fullPath);
        if ($realPath === false) {
            throw new \RuntimeException(
                sprintf('Template file not found: "%s"', $path)
            );
        }

        // Ensure resolved path is within template directory
        $realTemplateDir = realpath($templateDir);
        if ($realTemplateDir === false || ! str_starts_with($realPath, $realTemplateDir . \DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException(
                sprintf('Template file is outside allowed directory: "%s"', $path)
            );
        }

        return $realPath;
    }
}
