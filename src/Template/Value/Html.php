<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

use Stringable;

/**
 * Wrapper for trusted HTML content that should not be escaped.
 *
 * Use this only for content you trust (e.g., from your own system):
 * ```php
 * <div class="content"><?= $this->content ?></div>
 * ```
 *
 * WARNING: Never wrap user input in Html - use Text instead.
 */
final readonly class Html implements Stringable
{
    public function __construct(
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create Html from a trusted source.
     * Alias for constructor for readability.
     */
    public static function trusted(string $html): self
    {
        return new self($html);
    }
}
