<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use Closure;
use PiedWeb\Splates\Template\Attribute\Inject;

/**
 * Base class for all templates.
 *
 * Provides typed access to engine services and helper methods for
 * composition-based template rendering.
 *
 * ```php
 * class ProfileTpl extends TemplateAbstract
 * {
 *     #[Inject]
 *     public MyService $service;  // Auto-injected from Engine globals
 *
 *     public function __construct(
 *         #[TemplateData]
 *         public User $user,
 *     ) {}
 *
 *     public function __invoke(): void
 *     {
 *         echo $this->render(new LayoutTpl(
 *             title: $this->user->getName(),
 *             content: fn() => $this->renderContent(),
 *         ));
 *     }
 *
 *     private function renderContent(): string
 *     {
 *         return $this->capture(function() { ?>
 *             <h1><?= $this->e($this->user->getName()) ?></h1>
 *         <?php });
 *     }
 * }
 * ```
 */
abstract class TemplateAbstract implements TemplateClassInterface
{
    /**
     * Fetch/render helper - renders child templates.
     */
    #[Inject]
    protected TemplateFetch $f;

    /**
     * Escape helper - escapes strings for HTML.
     */
    #[Inject]
    protected TemplateEscape $e;

    /**
     * Render a child template and return the output as a string.
     *
     * @param array<string, mixed> $data Additional data to pass to the template
     */
    final protected function render(TemplateClassInterface $template, array $data = []): string
    {
        return ($this->f)($template, $data, false);
    }

    /**
     * Capture output from a callback as a string.
     *
     * Useful for creating slot content:
     * ```php
     * $content = $this->capture(function() { ?>
     *     <h1>Hello World</h1>
     * <?php });
     * ```
     *
     * @param Closure(): void $callback
     */
    final protected function capture(Closure $callback): string
    {
        ob_start();
        $callback();
        $output = ob_get_clean();

        return $output === false ? '' : $output;
    }

    /**
     * Create a slot (lazy-evaluated content) for passing to layout components.
     *
     * Syntactic sugar to avoid verbose nested closures:
     * ```php
     * // Before (ugly):
     * content: fn() => $this->capture(function() { ?>
     *     <p>Content</p>
     * <?php })
     *
     * // After (clean):
     * content: $this->slot(function() { ?>
     *     <p>Content</p>
     * <?php })
     * ```
     *
     * @param Closure(): void $callback
     * @return Closure(): string
     */
    final protected function slot(Closure $callback): Closure
    {
        return fn (): string => $this->capture($callback);
    }

    /**
     * Escape a value for safe HTML output.
     *
     * Shorthand for $this->e(...).
     */
    final protected function e(int|float|string|bool $value): string
    {
        return ($this->e)($value);
    }
}
