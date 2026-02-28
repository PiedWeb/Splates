<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template\Attribute;

use Attribute;

/**
 * Marks a constructor parameter for template data binding.
 *
 * Use on constructor parameters for IDE autocompletion:
 * ```php
 * public function __construct(
 *     #[TemplateData]
 *     public User $user,
 * ) {}
 * ```
 *
 * For global service injection, use #[Inject] on properties instead.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class TemplateData
{
}
