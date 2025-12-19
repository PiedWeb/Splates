<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

use JsonException;
use Stringable;

/**
 * JSON-encodes content for JavaScript context.
 *
 * Use this for values embedded in JavaScript:
 * ```php
 * <script>
 *     const config = <?= $this->config ?>;
 *     const name = <?= $this->name ?>;
 * </script>
 * ```
 *
 * Safely encodes any PHP value as JSON with XSS protection.
 */
final readonly class Js implements Stringable
{
    public function __construct(
        private mixed $value,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode(
            $this->value,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Get the raw PHP value.
     */
    public function raw(): mixed
    {
        return $this->value;
    }
}
