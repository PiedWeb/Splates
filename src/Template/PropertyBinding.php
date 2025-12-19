<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

/**
 * Represents a property that needs to be bound from Engine globals.
 *
 * Only properties with #[TemplateData(global: true)] create PropertyBindings.
 * Constructor parameters are handled by PHP's native constructor invocation.
 */
final readonly class PropertyBinding
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
