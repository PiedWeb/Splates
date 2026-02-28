<?php

declare(strict_types=1);

namespace PiedWeb\Splates;

use PiedWeb\Splates\Template\InjectResolver;
use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateClass;
use PiedWeb\Splates\Template\TemplateClassInterface;
use PiedWeb\Splates\Template\TemplateFile;

/**
 * Template API and environment settings storage.
 *
 * Usage:
 * ```php
 * $engine = new Engine();
 * $engine->addGlobal('ext', $templateExtension);  // Available to ALL templates
 *
 * // Render with full IDE autocompletion
 * echo $engine->render(new ProfileTpl(user: $user, title: 'Profile'));
 * ```
 */
class Engine
{
    /**
     * Global services/data available to all templates.
     *
     * @var array<string, mixed>
     */
    private array $globals = [];

    /**
     * Inject resolver with caching.
     */
    private InjectResolver $injectResolver;

    /**
     * Template directory for file-based templates.
     */
    private readonly string $templateDir;

    /**
     * Create new Engine instance.
     *
     * @param string|null $templateDir Directory for file-based templates (auto-detected if null)
     * @param string|null $cacheDir Directory for caching reflection data (production)
     */
    public function __construct(?string $templateDir = null, ?string $cacheDir = null)
    {
        $this->templateDir = $templateDir ?? $this->detectProjectRoot();
        $this->injectResolver = new InjectResolver($cacheDir);
    }

    /**
     * Add a global service or value available to all templates.
     *
     * Use with #[Inject] on properties:
     * ```php
     * class ProfileTpl extends TemplateAbstract
     * {
     *     #[Inject]
     *     public TemplateExtension $ext;  // Auto-injected
     * }
     * ```
     */
    public function addGlobal(string $name, mixed $value): static
    {
        $this->globals[$name] = $value;

        return $this;
    }

    /**
     * Get all registered globals.
     *
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        return $this->globals;
    }

    /**
     * Get a specific global value.
     */
    public function getGlobal(string $name): mixed
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * Get the inject resolver.
     */
    public function getInjectResolver(): InjectResolver
    {
        return $this->injectResolver;
    }

    /**
     * Get the template directory.
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * Get preassigned data for a template (legacy support).
     *
     * @return array<string, mixed>
     */
    public function getData(?string $template = null): array
    {
        // In v4, data is passed via constructor - this is for backwards compatibility
        return [];
    }

    /**
     * Create a template instance for rendering.
     *
     * @param array<string, mixed> $data Additional data (legacy support)
     */
    public function make(TemplateClassInterface $template, array $data = []): Template
    {
        $templateInstance = new TemplateClass($this, $template);
        $templateInstance->data($data);

        return $templateInstance;
    }

    /**
     * Render a template to string.
     *
     * @param TemplateClassInterface|string $template Template instance or file path
     * @param array<string, mixed> $data Additional data
     */
    public function render(TemplateClassInterface|string $template, array $data = []): string
    {
        if (is_string($template)) {
            // File-based template
            $templateInstance = new TemplateFile($this, $template);
            $templateInstance->data($data);

            return $templateInstance->render();
        }

        // Class-based template (existing logic)
        return $this->make($template, $data)->render();
    }

    /**
     * Clear all caches (useful for development).
     */
    public function clearCache(): void
    {
        $this->injectResolver->clearCache();
    }

    /**
     * Detect project root by finding composer.json.
     */
    private function detectProjectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== '/') {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        // Fallback to current working directory
        return getcwd() ?: __DIR__;
    }
}
