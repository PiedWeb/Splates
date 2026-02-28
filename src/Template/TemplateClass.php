<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use RuntimeException;
use Override;
use PiedWeb\Splates\Engine;
use PiedWeb\Splates\Template\Value\Text;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Handles rendering of class-based templates.
 *
 * This class:
 * 1. Creates the TemplateFetch and TemplateEscape helpers
 * 2. Injects all #[Inject] properties (framework helpers + globals)
 * 3. Calls __invoke() on the template
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

    public function __construct(
        Engine $engine,
        protected TemplateClassInterface $templateClass,
    ) {
        $this->engine = $engine;

        $this->templateFetch = new TemplateFetch($this->engine, $this);
        $this->templateEscape = new TemplateEscape($this);
    }

    #[Override]
    protected function display(): void
    {
        // 1. Inject all #[Inject] properties (helpers + globals)
        $this->injectProperties();

        // 2. Call display with parameter injection if needed
        $this->callDisplay();
    }

    /**
     * Inject all properties marked with #[Inject] attribute.
     *
     * - TemplateFetch/TemplateEscape types: inject per-template instances
     * - Other types: look up from Engine globals by property name
     */
    private function injectProperties(): void
    {
        $resolver = $this->engine->getInjectResolver();
        $className = $this->templateClass::class;
        $bindings = $resolver->resolve($className);
        $globals = $this->engine->getGlobals();

        foreach ($bindings as $binding) {
            $value = match ($binding->type) {
                TemplateFetch::class => $this->templateFetch,
                TemplateEscape::class => $this->templateEscape,
                default => $this->resolveGlobalValue($binding, $globals),
            };

            if ($value === null) {
                // Throw if a non-nullable #[Inject] property can't be resolved
                if ($binding->type !== null) {
                    $prop = $resolver->getReflectionProperty($className, $binding->propertyName);
                    $type = $prop->getType();
                    if ($type instanceof ReflectionNamedType && ! $type->allowsNull()) {
                        throw new RuntimeException(
                            \sprintf(
                                'Cannot inject property "%s::$%s": no global "%s" registered. Use $engine->addGlobal(\'%s\', ...) or make the property nullable.',
                                $className,
                                $binding->propertyName,
                                $binding->globalKey,
                                $binding->globalKey,
                            )
                        );
                    }
                }

                continue;
            }

            $resolver->getReflectionProperty($className, $binding->propertyName)
                ->setValue($this->templateClass, $value);
        }
    }

    /**
     * Resolve a value from Engine globals.
     *
     * @param array<string, mixed> $globals
     */
    private function resolveGlobalValue(InjectBinding $binding, array $globals): mixed
    {
        if (! isset($globals[$binding->globalKey])) {
            return null;
        }

        $value = $globals[$binding->globalKey];

        if ($binding->escape && \is_string($value)) {
            return new Text($value);
        }

        return $value;
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
