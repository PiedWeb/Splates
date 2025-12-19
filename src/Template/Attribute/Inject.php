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
 *
 * Example:
 * ```php
 * class Profile implements TemplateClassInterface
 * {
 *     #[Inject]
 *     protected TemplateFetch $f;
 *
 *     #[Inject]
 *     protected TemplateEscape $e;
 *
 *     public function display(): void
 *     {
 *         echo ($this->e)($this->name);
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Inject
{
}
