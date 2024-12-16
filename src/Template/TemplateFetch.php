<?php

namespace PiedWeb\Splates\Template;

use PiedWeb\Splates\Engine;

class TemplateFetch
{
    public function __construct(private readonly Engine $engine, private readonly Template $template)
    {

    }

    /** @param array<mixed> $data */
    public function __invoke(string|TemplateClassInterface $name, array $data = [], bool $useTemplateData = true): string
    {
        return $this->engine->render($name, $useTemplateData ? array_merge($this->template->data(), $data) : $data);
    }
}
