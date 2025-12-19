<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Attribute;

use Attribute;

/**
 * Marks a property or constructor parameter for template data binding.
 *
 * Use on constructor parameters for IDE autocompletion:
 * ```php
 * public function __construct(
 *     #[TemplateData]
 *     public User $user,
 * ) {}
 * ```
 *
 * Use on properties with `global: true` for auto-injection:
 * ```php
 * #[TemplateData(global: true)]
 * public TemplateExtension $ext;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class TemplateData
{
    public function __construct(
        /**
         * Auto-inject from Engine globals (property-only, not constructor).
         * Properties with global: true are injected after construction.
         */
        public readonly bool $global = false,

        /**
         * Auto-escape scalar values by wrapping them in Text.
         * Only applies to string values - objects are passed as-is.
         */
        public readonly bool $escape = false,
    ) {
    }
}
