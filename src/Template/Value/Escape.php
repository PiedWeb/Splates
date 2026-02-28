<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Value;

/**
 * Shared HTML escaping utilities.
 */
final class Escape
{
    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
