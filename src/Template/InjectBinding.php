<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

/**
 * Represents a property that needs to be injected via #[Inject].
 *
 * For TemplateFetch/TemplateEscape types, per-template instances are injected.
 * For other types, values are looked up from Engine globals by property name.
 */
final readonly class InjectBinding
{
    public function __construct(
        /**
         * The property name on the template class.
         */
        public string $propertyName,

        /**
         * The key to look up in Engine globals.
         * Usually same as propertyName unless customized.
         */
        public string $globalKey,

        /**
         * Whether to auto-escape scalar values.
         */
        public bool $escape,

        /**
         * The expected type (for validation/debugging).
         */
        public ?string $type,
    ) {
    }
}
