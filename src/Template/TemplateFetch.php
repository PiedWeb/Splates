<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use PiedWeb\Splates\Engine;

/**
 * Helper for rendering child templates.
 *
 * Used internally by TemplateAbstract::render().
 */
class TemplateFetch
{
    public function __construct(
        private readonly Engine $engine,
        private readonly Template $template,
    ) {
    }

    /**
     * Render a child template and return the output.
     *
     * @param array<string, mixed> $data Additional data to pass
     * @param bool $useTemplateData Whether to inherit parent template data
     */
    public function __invoke(TemplateClassInterface $template, array $data = [], bool $useTemplateData = true): string
    {
        return $this->engine->render(
            $template,
            $useTemplateData ? array_merge($this->template->data(), $data) : $data,
        );
    }
}
