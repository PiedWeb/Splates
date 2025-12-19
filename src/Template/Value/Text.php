<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

use Stringable;

/**
 * Auto-escapes content for HTML text context.
 *
 * Use this for user-provided text that should be safely displayed:
 * ```php
 * <h1><?= $this->name ?></h1>
 * ```
 *
 * The value is automatically escaped when converted to string.
 */
final readonly class Text implements Stringable
{
    private string $value;

    public function __construct(
        string|int|float|bool|Stringable $value,
    ) {
        $this->value = (string) $value;
    }

    public function __toString(): string
    {
        return htmlspecialchars($this->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Get the raw unescaped value.
     * Use sparingly - prefer letting Text handle escaping.
     */
    public function raw(): string
    {
        return $this->value;
    }
}
