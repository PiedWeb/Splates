<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

use Closure;
use Stringable;

/**
 * Wrapper for closures that return string content for use in templates.
 *
 * Use this for slot/content injection patterns:
 * ```php
 * public function __construct(
 *     #[TemplateData]
 *     public Slot $content,
 * ) {}
 *
 * // In template:
 * <main><?= $this->content ?></main>
 * ```
 */
final readonly class Slot implements Stringable
{
    /** @var Closure(): string */
    private Closure $closure;

    /** @param Closure(): string $closure */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public function __toString(): string
    {
        return ($this->closure)();
    }
}
