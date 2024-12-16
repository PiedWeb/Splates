<?php

namespace PiedWeb\Splates\Template;

use PiedWeb\Splates\Exception\TemplateNotFound;

interface ResolveTemplatePath
{
    /**
     * @throws TemplateNotFound if the template could not be properly resolved to a file path
     */
    public function __invoke(Name $name): string;
}
