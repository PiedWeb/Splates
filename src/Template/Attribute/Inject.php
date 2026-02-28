<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Attribute;

use Attribute;

/**
 * Marks a property for automatic injection by the template engine.
 *
 * Supported types:
 * - TemplateFetch: For rendering child templates
 * - TemplateEscape: For escaping values
 * - Any other type: Looked up from Engine globals by property name
 *
 * Example:
 * ```php
 * class Profile extends TemplateAbstract
 * {
 *     #[Inject]
 *     public AppService $app;  // Auto-injected from Engine globals
 *
 *     public function __invoke(): void
 *     {
 *         echo $this->e($this->app->getName());
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Inject
{
    public function __construct(
        /**
         * Auto-escape scalar values by wrapping them in Text.
         * Only applies to string values - objects are passed as-is.
         */
        public bool $escape = false,
    ) {
    }
}
