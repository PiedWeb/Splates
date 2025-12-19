<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

use Stringable;

/**
 * Escapes content for HTML attribute context.
 *
 * Use this for values that go inside HTML attributes:
 * ```php
 * <input value="<?= $this->value ?>" data-id="<?= $this->id ?>">
 * ```
 *
 * Uses stricter escaping than Text to prevent attribute injection.
 */
final readonly class Attr implements Stringable
{
    public function __construct(
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return htmlspecialchars($this->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get the raw unescaped value.
     */
    public function raw(): string
    {
        return $this->value;
    }
}
