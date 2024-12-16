<?php

namespace PiedWeb\Splates\Extension;

use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Template;

/**
 * Exemple Extension, do (almost) nothing
 */
class URI implements ExtensionInterface
{
    public Template $template;

    public function register(Engine $engine): void
    {
        $engine->registerFunction('uri', $this->runUri(...));
    }

    public function runUri(string $uri): string
    {
        return $uri;
    }
}
