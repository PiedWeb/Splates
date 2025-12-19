<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use ReflectionClass;
use ReflectionProperty;

/**
 * Resolves #[TemplateData(global: true)] properties from template classes.
 *
 * Uses multi-level caching:
 * 1. Memory cache (fastest, per-request)
 * 2. File cache (production, cross-request)
 * 3. Reflection (development, cache miss)
 */
final class TemplateDataResolver
{
    /**
     * Memory cache for resolved bindings.
     *
     * @var array<class-string, list<PropertyBinding>>
     */
    private static array $cache = [];

    public function __construct(
        /**
         * Directory for file-based cache.
         * null = disable file caching (development mode).
         */
        private readonly ?string $cacheDir = null,
    ) {
    }

    /**
     * Resolve all global property bindings for a template class.
     *
     * @param class-string<TemplateClassInterface> $className
     *
     * @return list<PropertyBinding>
     */
    public function resolve(string $className): array
    {
        // 1. Memory cache (fastest)
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        // 2. File cache (production)
        if ($this->cacheDir !== null) {
            $cacheFile = $this->getCacheFile($className);
            if (file_exists($cacheFile)) {
                /** @var array<int, array{propertyName: string, globalKey: string, escape: bool, type: ?string}> $bindings */
                $bindings = require $cacheFile;

                return self::$cache[$className] = $this->hydrateBindings($bindings);
            }
        }

        // 3. Reflection (dev or cache miss)
        $bindings = $this->reflectClass($className);

        // 4. Write file cache
        if ($this->cacheDir !== null) {
            $this->writeCache($className, $bindings);
        }

        return self::$cache[$className] = $bindings;
    }

    /**
     * Clear all caches.
     */
    public function clearCache(): void
    {
        self::$cache = [];

        if ($this->cacheDir !== null && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/splates_*.php');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Warm cache for a list of template classes.
     *
     * @param list<class-string<TemplateClassInterface>> $classNames
     */
    public function warmCache(array $classNames): void
    {
        foreach ($classNames as $className) {
            $this->resolve($className);
        }
    }

    /**
     * @param class-string $className
     *
     * @return list<PropertyBinding>
     */
    private function reflectClass(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $bindings = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attrs = $property->getAttributes(TemplateData::class);
            if ($attrs === []) {
                continue;
            }

            $attr = $attrs[0]->newInstance();

            // Only process global properties - constructor params don't need binding
            if (! $attr->global) {
                continue;
            }

            $type = $property->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            $bindings[] = new PropertyBinding(
                propertyName: $property->getName(),
                globalKey: $property->getName(),
                escape: $attr->escape,
                type: $typeName,
            );
        }

        return $bindings;
    }

    private function getCacheFile(string $className): string
    {
        return $this->cacheDir . '/splates_' . md5($className) . '.php';
    }

    /**
     * @param list<PropertyBinding> $bindings
     */
    private function writeCache(string $className, array $bindings): void
    {
        \assert($this->cacheDir !== null);

        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Convert bindings to exportable array format
        $data = [];
        foreach ($bindings as $binding) {
            $data[] = [
                'propertyName' => $binding->propertyName,
                'globalKey' => $binding->globalKey,
                'escape' => $binding->escape,
                'type' => $binding->type,
            ];
        }

        $code = "<?php\n\n// Cache for: " . $className . "\n// Generated: " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export($data, true) . ";\n";

        file_put_contents($this->getCacheFile($className), $code, LOCK_EX);
    }

    /**
     * Hydrate PropertyBinding objects from cached array data.
     *
     * @param array<int, array{propertyName: string, globalKey: string, escape: bool, type: ?string}> $data
     *
     * @return list<PropertyBinding>
     */
    private function hydrateBindings(array $data): array
    {
        $bindings = [];
        foreach ($data as $item) {
            $bindings[] = new PropertyBinding(
                propertyName: $item['propertyName'],
                globalKey: $item['globalKey'],
                escape: $item['escape'],
                type: $item['type'],
            );
        }

        return $bindings;
    }
}
