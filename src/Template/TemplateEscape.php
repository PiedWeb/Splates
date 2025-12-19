<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

/**
 * Helper for escaping values in templates.
 *
 * Used internally by TemplateAbstract::e().
 */
class TemplateEscape
{
    public function __construct(
        private readonly Template $template,
    ) {
    }

    /**
     * Escape a value for safe HTML output.
     */
    public function __invoke(int|float|string|bool $string): string
    {
        return $this->template->escape($string);
    }
}
