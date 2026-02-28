<?php

declare(strict_types=1);

namespace PiedWeb\Splates\Template;

use LogicException;
use PiedWeb\Splates\Template\Attribute\Inject;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Resolves #[Inject] properties from template classes.
 *
 * Uses multi-level caching:
 * 1. Memory cache (fastest, per-request)
 * 2. File cache (production, cross-request)
 * 3. Reflection (development, cache miss)
 */
final class InjectResolver
{
    /**
     * Memory cache for resolved bindings.
     *
     * @var array<class-string, list<InjectBinding>>
     */
    private static array $cache = [];

    /**
     * Cache for ReflectionProperty objects (needed for setting protected properties).
     *
     * @var array<class-string, array<string, ReflectionProperty>>
     */
    private static array $reflectionCache = [];

    public function __construct(
        /**
         * Directory for file-based cache.
         * null = disable file caching (development mode).
         */
        private readonly ?string $cacheDir = null,
    ) {
    }

    /**
     * Resolve all #[Inject] property bindings for a template class.
     *
     * @param class-string<TemplateClassInterface> $className
     *
     * @return list<InjectBinding>
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

                self::$cache[$className] = $this->hydrateBindings($bindings);
                // Rebuild reflection cache for these bindings
                $this->buildReflectionCache($className, self::$cache[$className]);

                return self::$cache[$className];
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
     * Get the cached ReflectionProperty for setting a property value.
     *
     * @param class-string $className
     */
    public function getReflectionProperty(string $className, string $propertyName): ReflectionProperty
    {
        return self::$reflectionCache[$className][$propertyName];
    }

    /**
     * Clear all caches.
     */
    public function clearCache(): void
    {
        self::$cache = [];
        self::$reflectionCache = [];

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
     * @return list<InjectBinding>
     */
    private function reflectClass(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $bindings = [];
        self::$reflectionCache[$className] = [];

        foreach ($reflection->getProperties() as $property) {
            $attrs = $property->getAttributes(Inject::class);
            if ($attrs === []) {
                continue;
            }

            $attr = $attrs[0]->newInstance();

            $type = $property->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            $propertyName = $property->getName();
            self::$reflectionCache[$className][$propertyName] = $property;

            $bindings[] = new InjectBinding(
                propertyName: $propertyName,
                globalKey: $propertyName,
                escape: $attr->escape,
                type: $typeName,
            );
        }

        return $bindings;
    }

    /**
     * Build reflection cache for bindings loaded from file cache.
     *
     * @param class-string $className
     * @param list<InjectBinding> $bindings
     */
    private function buildReflectionCache(string $className, array $bindings): void
    {
        if (isset(self::$reflectionCache[$className])) {
            return;
        }

        $reflection = new ReflectionClass($className);
        self::$reflectionCache[$className] = [];

        foreach ($bindings as $binding) {
            self::$reflectionCache[$className][$binding->propertyName] = $reflection->getProperty($binding->propertyName);
        }
    }

    private function getCacheFile(string $className): string
    {
        return $this->cacheDir . '/splates_' . md5($className) . '.php';
    }

    /**
     * @param list<InjectBinding> $bindings
     */
    private function writeCache(string $className, array $bindings): void
    {
        if ($this->cacheDir === null) {
            throw new LogicException('Cache directory must be set for file caching');
        }

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
     * Hydrate InjectBinding objects from cached array data.
     *
     * @param array<int, array{propertyName: string, globalKey: string, escape: bool, type: ?string}> $data
     *
     * @return list<InjectBinding>
     */
    private function hydrateBindings(array $data): array
    {
        return array_values(array_map(fn (array $item): InjectBinding => new InjectBinding(...$item), $data));
    }
}
