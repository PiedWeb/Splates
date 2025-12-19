<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use Override;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Value\Text;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Handles rendering of class-based templates.
 *
 * This class:
 * 1. Creates the TemplateFetch and TemplateEscape helpers
 * 2. Injects helpers via #[Inject] properties or __invoke() parameters
 * 3. Injects global properties (from Engine::addGlobal)
 * 4. Calls __invoke() on the template
 */
class TemplateClass extends Template
{
    protected TemplateFetch $templateFetch;

    protected TemplateEscape $templateEscape;

    /**
     * Cache for __invoke() method parameter analysis.
     *
     * @var array<class-string, list<string>>
     */
    private static array $invokeParamsCache = [];

    /**
     * Cache for #[Inject] property analysis.
     *
     * @var array<class-string, array<string, string>>
     */
    private static array $injectPropsCache = [];

    public function __construct(
        Engine $engine,
        protected TemplateClassInterface $templateClass,
    ) {
        $this->engine = $engine;

        // Data is now passed via constructor, but we still support Engine data for compatibility
        $this->data($this->engine->getData($templateClass::class));

        $this->templateFetch = new TemplateFetch($this->engine, $this);
        $this->templateEscape = new TemplateEscape($this);
    }

    #[Override]
    protected function display(): void
    {
        // 1. Inject #[Inject] properties
        $this->injectHelperProperties();

        // 2. Inject global properties
        $this->injectGlobalProperties();

        // 3. Call display with parameter injection if needed
        $this->callDisplay();
    }

    /**
     * Cache for ReflectionProperty objects.
     *
     * @var array<class-string, array<string, \ReflectionProperty>>
     */
    private static array $injectReflectionCache = [];

    /**
     * Inject properties marked with #[Inject] attribute.
     */
    private function injectHelperProperties(): void
    {
        $className = $this->templateClass::class;

        if (! isset(self::$injectPropsCache[$className])) {
            $this->buildInjectCache($className);
        }

        foreach (self::$injectPropsCache[$className] as $propertyName => $typeName) {
            $value = match ($typeName) {
                TemplateFetch::class => $this->templateFetch,
                TemplateEscape::class => $this->templateEscape,
                default => null,
            };

            if ($value !== null) {
                self::$injectReflectionCache[$className][$propertyName]->setValue($this->templateClass, $value);
            }
        }
    }

    /**
     * Build cache for #[Inject] properties including reflection objects.
     *
     * @param class-string $className
     */
    private function buildInjectCache(string $className): void
    {
        $reflection = new ReflectionClass($className);
        self::$injectPropsCache[$className] = [];
        self::$injectReflectionCache[$className] = [];

        foreach ($reflection->getProperties() as $property) {
            $attrs = $property->getAttributes(Inject::class);
            if ($attrs === []) {
                continue;
            }

            $type = $property->getType();
            if (! $type instanceof ReflectionNamedType) {
                continue;
            }

            $propertyName = $property->getName();
            self::$injectPropsCache[$className][$propertyName] = $type->getName();
            self::$injectReflectionCache[$className][$propertyName] = $property;
        }
    }

    /**
     * Cache for ReflectionMethod objects for __invoke.
     *
     * @var array<class-string, ReflectionMethod>
     */
    private static array $invokeMethodCache = [];

    /**
     * Call __invoke() with parameter injection if the method expects parameters.
     */
    private function callDisplay(): void
    {
        $className = $this->templateClass::class;

        if (! isset(self::$invokeParamsCache[$className])) {
            $this->buildInvokeCache($className);
        }

        $paramTypes = self::$invokeParamsCache[$className];

        if ($paramTypes === []) {
            self::$invokeMethodCache[$className]->invoke($this->templateClass);

            return;
        }

        // Build arguments based on parameter types
        $args = [];
        foreach ($paramTypes as $typeName) {
            $args[] = match ($typeName) {
                TemplateFetch::class => $this->templateFetch,
                TemplateEscape::class => $this->templateEscape,
                default => null,
            };
        }

        self::$invokeMethodCache[$className]->invoke($this->templateClass, ...$args);
    }

    /**
     * Build cache for __invoke() method reflection.
     *
     * @param class-string $className
     */
    private function buildInvokeCache(string $className): void
    {
        $reflection = new ReflectionMethod($className, '__invoke');
        self::$invokeMethodCache[$className] = $reflection;

        $params = [];
        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType) {
                $params[] = $type->getName();
            }
        }

        self::$invokeParamsCache[$className] = $params;
    }

    /**
     * Inject properties marked with #[TemplateData(global: true)] from Engine globals.
     */
    private function injectGlobalProperties(): void
    {
        $resolver = $this->engine->getTemplateDataResolver();
        $bindings = $resolver->resolve($this->templateClass::class);
        $globals = $this->engine->getGlobals();

        foreach ($bindings as $binding) {
            if (! isset($globals[$binding->globalKey])) {
                // Global not set - skip (property might have a default)
                continue;
            }

            $value = $globals[$binding->globalKey];

            // Auto-escape if configured
            if ($binding->escape && \is_string($value)) {
                $value = new Text($value);
            }

            $this->templateClass->{$binding->propertyName} = $value;
        }
    }

    #[Override]
    public function exists(): bool
    {
        return true;
    }

    #[Override]
    public function path(): string
    {
        return $this->templateClass::class;
    }
}
