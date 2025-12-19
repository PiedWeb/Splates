<?php

declare(strict_types=1);

namespace PiedWeb\Splates;

use PiedWeb\Splates\Template\Template;
use PiedWeb\Splates\Template\TemplateClass;
use PiedWeb\Splates\Template\TemplateClassInterface;
use PiedWeb\Splates\Template\TemplateDataResolver;

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
     * Template data resolver with caching.
     */
    private TemplateDataResolver $templateDataResolver;

    /**
     * Create new Engine instance.
     *
     * @param string|null $cacheDir Directory for caching reflection data (production)
     */
    public function __construct(?string $cacheDir = null)
    {
        $this->templateDataResolver = new TemplateDataResolver($cacheDir);
    }

    /**
     * Add a global service or value available to all templates.
     *
     * Use with #[TemplateData(global: true)] on properties:
     * ```php
     * class ProfileTpl extends TemplateAbstract
     * {
     *     #[TemplateData(global: true)]
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
     * Get the template data resolver.
     */
    public function getTemplateDataResolver(): TemplateDataResolver
    {
        return $this->templateDataResolver;
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
     * @param array<string, mixed> $data Additional data (legacy support)
     */
    public function render(TemplateClassInterface $template, array $data = []): string
    {
        return $this->make($template, $data)->render();
    }

    /**
     * Clear all caches (useful for development).
     */
    public function clearCache(): void
    {
        $this->templateDataResolver->clearCache();
    }
}
