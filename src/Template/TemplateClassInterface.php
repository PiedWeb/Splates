<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

/**
 * Interface for class-based templates.
 *
 * Templates can be implemented in several ways:
 *
 * 1. Minimal (no helpers needed):
 * ```php
 * class Hello implements TemplateClassInterface
 * {
 *     public function __construct(public string $name) {}
 *
 *     public function __invoke(): void {
 *         echo "Hello, {$this->name}!";
 *     }
 * }
 * ```
 *
 * 2. With __invoke() parameter injection:
 * ```php
 * class Profile implements TemplateClassInterface
 * {
 *     public function __invoke(TemplateFetch $f, TemplateEscape $e): void {
 *         echo $e($this->name);
 *     }
 * }
 * ```
 *
 * 3. With #[Inject] property injection:
 * ```php
 * class Profile implements TemplateClassInterface
 * {
 *     #[Inject]
 *     protected TemplateFetch $f;
 *
 *     public function __invoke(): void {
 *         echo ($this->f)(new Layout(...));
 *     }
 * }
 * ```
 *
 * 4. Extending TemplateAbstract (full helper methods):
 * ```php
 * class Profile extends TemplateAbstract
 * {
 *     public function __invoke(): void {
 *         echo $this->render(new Layout(...));
 *     }
 * }
 * ```
 */
interface TemplateClassInterface
{
}
