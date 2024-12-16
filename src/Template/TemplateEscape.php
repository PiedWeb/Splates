<?php

namespace PiedWeb\Splates\Template;

class TemplateEscape
{
    public function __construct(private readonly Template $template)
    {

    }

    public function __invoke(string $string, ?string $functions = null): string
    {
        return $this->template->escape($string);
    }
}
