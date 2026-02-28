<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Value\Escape as EscapeUtil;
use Stringable;
use Throwable;

/**
 * Base template container.
 *
 * Provides the core rendering functionality and escape helpers.
 * This class is extended by TemplateClass for class-based templates.
 */
class Template implements Stringable
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(protected Engine $engine)
    {
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Get or set template data.
     *
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>
     */
    public function data(?array $data = null): array
    {
        if ($data === null) {
            return $this->data;
        }

        return $this->data = array_merge($this->data, $data);
    }

    /**
     * Render the template.
     *
     * @param array<string, mixed> $data
     */
    public function render(array $data = []): string
    {
        $this->data($data);

        try {
            $level = ob_get_level();
            ob_start();
            $this->display();
            $content = ob_get_clean();

            return $content === false ? '' : $content;
        } catch (Throwable $throwable) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $throwable;
        }
    }

    /**
     * Override in subclasses to output template content.
     */
    protected function display(): void
    {
        // Subclasses override this
    }

    /**
     * Check if template exists.
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * Get template path.
     */
    public function path(): string
    {
        return '';
    }

    /**
     * Escape a value for safe HTML output.
     */
    public function escape(int|float|string|bool $string): string
    {
        return EscapeUtil::html((string) $string);
    }

    /**
     * Shorthand for escape().
     */
    public function e(int|float|string|bool $string): string
    {
        return $this->escape($string);
    }
}
